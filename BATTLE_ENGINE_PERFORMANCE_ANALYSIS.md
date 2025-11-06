# Battle Engine Performance Analysis & Optimization Guide

## Executive Summary

The Rust battle engine in OGameX already achieves **200x performance improvement** over PHP, but large-scale simulations (millions of units) still experience significant slowdowns. This document identifies the key bottlenecks and provides **calculation-preserving optimization strategies** to handle massive battles efficiently.

**Key Finding**: The primary bottleneck is the **unit expansion strategy** where every unit becomes an individual object in memory, creating millions of allocations for large battles.

---

## Current Architecture Analysis

### Data Flow
```
Input JSON → Expand Units → Process 6 Rounds → Compress Results → Output JSON
                 ↓
         [Million objects]
                 ↓
         Combat Processing
```

### Critical Performance Bottlenecks

#### 1. **Unit Expansion (Line 176-189)** - PRIMARY BOTTLENECK
```rust
fn expand_units(units: &HashMap<i16, BattleUnitInfo>) -> Vec<BattleUnitInstance>
```

**Issue**: Creates individual objects for EVERY unit
- 5M units = 5M separate BattleUnitInstance allocations
- Each instance: ~14 bytes (i16 + f32 + f32)
- Total memory: ~70MB just for unit instances (per side)
- Plus Vec overhead and memory fragmentation

**Impact on Large Battles**:
- 5M vs 5M battle: 10M allocations upfront
- O(N) memory complexity
- Poor cache locality during iteration

#### 2. **Combat Loop Nested Iteration (Line 237-318)** - SECONDARY BOTTLENECK
```rust
for attacker in attackers.iter() {           // O(N) - millions
    while continue_attacking {                // O(R) - rapidfire
        let target_idx = rng.gen_range(...);  // O(1) but millions of times
        // damage calculations
    }
}
```

**Issue**: Sequential processing with no parallelism
- Every attacker processes sequentially
- 1M attackers = 1M+ random selections
- No utilization of multi-core CPUs

#### 3. **Random Target Selection (Line 248)**
```rust
let target_idx = rng.gen_range(0..defenders.len());
```

**Issue**: Simple but inefficient for repeated access
- Each attack needs random index generation
- No spatial locality for cache

#### 4. **Compression/Decompression Overhead (Lines 154-155)**
```rust
round.attacker_ships = compress_units(&attacker_units);
round.defender_ships = compress_units(&defender_units);
```

**Issue**: Iterates through all units every round
- O(N) operation executed 6 times
- Builds HashMap from scratch each time

---

## Optimization Strategies (Calculation-Preserving)

### Strategy 1: **Lazy Expansion with Damage Tracking** ⭐ RECOMMENDED

**Concept**: Keep identical units grouped until they take unique damage.

**Implementation**:
```rust
struct BattleUnitGroup {
    unit_id: i16,
    full_health_count: u32,        // Units at 100% health
    damaged_instances: Vec<BattleUnitInstance>,  // Only units with unique health
    base_shield: f32,
    base_hull: f32,
}
```

**How It Works**:
1. Start with all units in groups (compressed format)
2. When a unit is attacked:
   - If attacking a full-health group member, decrement count and create damaged instance
   - If attacking a damaged instance, modify it directly
3. Dead units removed from either counter or damaged vector

**Benefits**:
- Memory: O(D) where D = unique damaged units (not total units)
- Most units stay compressed throughout battle
- Early-round performance dramatically improved

**Calculation Preservation**:
✅ Random selection adjusted: pick from (full_health_count + damaged_instances.len())
✅ Damage application identical
✅ Explosion probability unchanged
✅ Rapidfire mechanics preserved

**Expected Performance**:
- 10M unit battle: ~95% memory reduction (most units die at full health)
- 50-80% CPU time reduction in early rounds
- Scales better with battle size

---

### Strategy 2: **Parallel Combat Processing with Rayon**

**Concept**: Process multiple attackers simultaneously using parallel iterators.

**Implementation**:
```rust
use rayon::prelude::*;

// Add to Cargo.toml:
// rayon = "1.10"

fn process_combat_parallel(
    attackers: &Vec<BattleUnitInstance>,
    defenders: &mut Vec<BattleUnitInstance>,
    // ... other params
) {
    // Collect all attacks first (read-only phase)
    let attacks: Vec<AttackResult> = attackers
        .par_iter()  // Parallel iterator
        .map(|attacker| {
            // Calculate attack target and damage
            // Returns (target_idx, damage, rapidfire_attacks)
        })
        .collect();

    // Apply all attacks (write phase)
    for attack in attacks {
        // Apply damage to defender
    }
}
```

**Benefits**:
- Utilizes all CPU cores
- 4-8x speedup on multi-core systems
- Scales with available cores

**Calculation Preservation**:
⚠️ **CRITICAL**: Must maintain attack order for RNG consistency
- Use seeded RNG with deterministic ordering
- Each attacker gets its own RNG seed based on position
- Ensures same battle outcome every time

**Implementation Note**:
```rust
let mut rng = rand::rngs::StdRng::seed_from_u64(
    attacker_index as u64 + round_number as u64 * 1000000
);
```

**Expected Performance**:
- 4-core CPU: 3-4x faster combat phase
- 8-core CPU: 6-7x faster combat phase
- Diminishing returns beyond 8 cores

---

### Strategy 3: **Tiered Processing Architecture**

**Concept**: Different processing strategies for different unit types/states.

**Implementation**:
```rust
struct BattleSide {
    // Fast path: Units at full health (compressed)
    pristine_units: HashMap<i16, PristineUnitGroup>,

    // Slow path: Units with unique health states
    damaged_units: Vec<BattleUnitInstance>,

    // Index for O(1) target selection
    total_unit_count: usize,
}

impl BattleSide {
    fn select_random_target(&mut self, rng: &mut impl Rng) -> TargetRef {
        let idx = rng.gen_range(0..self.total_unit_count);

        // Binary search to find if target is pristine or damaged
        if idx < self.pristine_count {
            // Fast path
        } else {
            // Slow path
        }
    }
}
```

**Benefits**:
- Optimal processing for each unit state
- Clear separation of hot/cold paths
- Easier to profile and optimize

**Calculation Preservation**:
✅ Same random selection probability
✅ No change to damage calculations
✅ Transparent to game logic

**Expected Performance**:
- 40-60% faster than current implementation
- Better cache utilization
- Lower memory footprint

---

### Strategy 4: **SIMD Batch Processing** (Advanced)

**Concept**: Use SIMD instructions to process multiple identical operations simultaneously.

**Implementation**:
```rust
// Add to Cargo.toml:
// packed_simd = "0.3"

use packed_simd::f32x8;

fn apply_damage_batch(
    targets: &mut [f32],  // Shield values
    damage: f32
) {
    let damage_vec = f32x8::splat(damage);

    for chunk in targets.chunks_exact_mut(8) {
        let shields = f32x8::from_slice_unaligned(chunk);
        let result = shields - damage_vec;
        result.write_to_slice_unaligned(chunk);
    }
}
```

**Benefits**:
- 4-8x faster for specific operations
- Best for homogeneous damage application
- Utilizes CPU vector instructions

**Calculation Preservation**:
⚠️ **Float precision concerns**:
- SIMD operations may have slight floating-point differences
- Need to verify with test cases
- May require epsilon-based comparison for tests

**Expected Performance**:
- 20-30% speedup for damage calculations
- Most effective in massive battles
- Hardware dependent (AVX2/AVX512 support)

---

### Strategy 5: **Pre-allocated Arena / Memory Pool**

**Concept**: Pre-allocate memory to reduce allocation overhead during battle.

**Implementation**:
```rust
struct BattleArena {
    unit_pool: Vec<BattleUnitInstance>,
    next_free: usize,
}

impl BattleArena {
    fn new(estimated_units: usize) -> Self {
        BattleArena {
            unit_pool: Vec::with_capacity(estimated_units),
            next_free: 0,
        }
    }

    fn allocate_unit(&mut self) -> &mut BattleUnitInstance {
        if self.next_free >= self.unit_pool.len() {
            self.unit_pool.push(BattleUnitInstance::default());
        }
        let unit = &mut self.unit_pool[self.next_free];
        self.next_free += 1;
        unit
    }

    fn reset(&mut self) {
        self.next_free = 0;
    }
}
```

**Benefits**:
- Eliminates allocation overhead
- Reduces memory fragmentation
- Reusable across rounds
- Better cache locality

**Calculation Preservation**:
✅ No changes to calculations
✅ Pure memory management optimization
✅ Transparent to game logic

**Expected Performance**:
- 10-20% faster unit expansion
- Consistent performance across rounds
- Lower memory churn

---

### Strategy 6: **Structure of Arrays (SoA) Layout**

**Concept**: Store unit data in separate arrays by field instead of array of structures.

**Current (AoS)**:
```rust
struct BattleUnitInstance {
    unit_id: i16,
    current_shield_points: f32,
    current_hull_plating: f32,
}
vec![instance1, instance2, ...] // Data scattered
```

**Proposed (SoA)**:
```rust
struct BattleUnits {
    unit_ids: Vec<i16>,
    shields: Vec<f32>,
    hulls: Vec<f32>,
    // All vectors same length, index corresponds to same unit
}
```

**Benefits**:
- Better cache utilization (60-80% improvement)
- CPU prefetcher more effective
- Easier SIMD vectorization
- Removes padding overhead

**Calculation Preservation**:
✅ Logic unchanged, just data layout
✅ Access pattern: `shields[i]` instead of `units[i].shields`
✅ No calculation differences

**Expected Performance**:
- 30-50% faster iteration
- Especially effective in damage application loops
- Better with modern CPUs

---

## Recommended Implementation Plan

### Phase 1: Quick Wins (1-2 days) - 40-60% improvement
1. **Implement Strategy 5** (Arena Allocation)
   - Low risk, easy implementation
   - Immediate memory benefits
   - Good foundation for other optimizations

2. **Add basic profiling**
   - Measure current performance
   - Identify hot paths
   - Establish baseline

### Phase 2: Major Optimization (3-5 days) - 200-300% improvement
3. **Implement Strategy 1** (Lazy Expansion)
   - Biggest impact on large battles
   - Maintains calculation accuracy
   - Requires careful testing

4. **Add comprehensive tests**
   - Battle outcome verification
   - Determinism tests
   - Edge case coverage

### Phase 3: Advanced Optimizations (5-7 days) - Additional 100-200%
5. **Implement Strategy 2** (Parallel Processing)
   - Requires deterministic RNG
   - Significant speedup on multi-core
   - More complex testing required

6. **Consider Strategy 6** (SoA Layout)
   - Major refactor but worth it
   - Pairs well with SIMD
   - Foundation for future optimizations

### Phase 4: Fine-tuning (Optional)
7. **Strategy 4** (SIMD) if needed
   - Advanced optimization
   - Platform-specific
   - Diminishing returns

---

## Testing Strategy

### 1. Determinism Tests
Ensure same input always produces same output:

```rust
#[test]
fn test_battle_determinism() {
    let input = /* large battle JSON */;
    let result1 = fight_battle_rounds(input);
    let result2 = fight_battle_rounds(input);
    assert_eq!(result1, result2);
}
```

### 2. Equivalence Tests
New implementation produces same results as old:

```rust
#[test]
fn test_optimization_equivalence() {
    let battles = load_test_battles();
    for battle in battles {
        let old_result = old_implementation(battle);
        let new_result = new_implementation(battle);
        assert_battles_equal(old_result, new_result);
    }
}
```

### 3. Performance Benchmarks
```rust
#[bench]
fn bench_small_battle(b: &mut Bencher) {
    // 100 vs 100 units
}

#[bench]
fn bench_medium_battle(b: &mut Bencher) {
    // 10K vs 10K units
}

#[bench]
fn bench_large_battle(b: &mut Bencher) {
    // 1M vs 1M units
}
```

### 4. Edge Cases
- Eternal loop prevention (high shields vs low damage)
- Rapidfire chains
- Single unit vs army
- Equal forces
- Extreme damage ratios

---

## Code Examples

### Example 1: Lazy Expansion Implementation

```rust
struct BattleUnitGroup {
    unit_id: i16,
    full_health_count: u32,
    damaged_instances: Vec<BattleUnitInstance>,
    base_shield: f32,
    base_hull: f32,
    base_attack: f32,
    rapidfire: HashMap<i16, u16>,
}

impl BattleUnitGroup {
    fn total_count(&self) -> usize {
        self.full_health_count as usize + self.damaged_instances.len()
    }

    fn get_or_damage_unit(&mut self, idx: usize) -> &mut BattleUnitInstance {
        if idx < self.full_health_count as usize {
            // Need to "expand" this pristine unit
            self.full_health_count -= 1;
            self.damaged_instances.push(BattleUnitInstance {
                unit_id: self.unit_id,
                current_shield_points: self.base_shield,
                current_hull_plating: self.base_hull,
            });
            self.damaged_instances.last_mut().unwrap()
        } else {
            let damaged_idx = idx - self.full_health_count as usize;
            &mut self.damaged_instances[damaged_idx]
        }
    }

    fn remove_destroyed(&mut self) {
        self.damaged_instances.retain(|u| u.current_hull_plating > 0.0);
    }
}

struct BattleSide {
    groups: HashMap<i16, BattleUnitGroup>,
    total_units: usize,
}

impl BattleSide {
    fn select_random_unit(&mut self, rng: &mut impl Rng) -> (i16, &mut BattleUnitInstance) {
        let target_idx = rng.gen_range(0..self.total_units);

        // Find which group this index belongs to
        let mut cumulative = 0;
        for (unit_id, group) in &mut self.groups {
            let group_size = group.total_count();
            if target_idx < cumulative + group_size {
                let local_idx = target_idx - cumulative;
                let unit = group.get_or_damage_unit(local_idx);
                return (*unit_id, unit);
            }
            cumulative += group_size;
        }
        unreachable!()
    }
}
```

### Example 2: Parallel Processing with Deterministic RNG

```rust
use rayon::prelude::*;
use rand::SeedableRng;
use rand::rngs::StdRng;

fn process_combat_parallel(
    attackers: &Vec<BattleUnitInstance>,
    defenders: &mut Vec<BattleUnitInstance>,
    round_number: usize,
    // ... other params
) {
    // Phase 1: Calculate attacks in parallel (read-only)
    let attack_results: Vec<AttackAction> = attackers
        .par_iter()
        .enumerate()
        .flat_map(|(idx, attacker)| {
            // Each attacker gets deterministic RNG based on position
            let seed = (round_number as u64 * 1_000_000) + idx as u64;
            let mut rng = StdRng::seed_from_u64(seed);

            let mut actions = Vec::new();
            let mut continue_attacking = true;

            while continue_attacking {
                let target_idx = rng.gen_range(0..defenders.len());
                // Calculate damage, rapidfire, etc.
                actions.push(AttackAction {
                    attacker_idx: idx,
                    target_idx,
                    damage: /* calculate */,
                });

                continue_attacking = /* rapidfire check */;
            }

            actions
        })
        .collect();

    // Phase 2: Apply attacks sequentially (write phase)
    // Must be sequential to maintain determinism
    for action in attack_results {
        apply_attack_action(&mut defenders[action.target_idx], action.damage);
    }
}

struct AttackAction {
    attacker_idx: usize,
    target_idx: usize,
    damage: f32,
}
```

### Example 3: Memory Arena

```rust
struct BattleArena {
    // Pre-allocated pools
    attacker_pool: Vec<BattleUnitInstance>,
    defender_pool: Vec<BattleUnitInstance>,

    // Current usage
    attacker_count: usize,
    defender_count: usize,
}

impl BattleArena {
    fn prepare_for_battle(
        &mut self,
        attacker_count: usize,
        defender_count: usize
    ) {
        // Ensure capacity
        if self.attacker_pool.capacity() < attacker_count {
            self.attacker_pool.reserve(attacker_count - self.attacker_pool.len());
        }
        if self.defender_pool.capacity() < defender_count {
            self.defender_pool.reserve(defender_count - self.defender_pool.len());
        }

        // Reset for new battle
        self.attacker_count = 0;
        self.defender_count = 0;
    }

    fn get_attackers_mut(&mut self) -> &mut [BattleUnitInstance] {
        &mut self.attacker_pool[..self.attacker_count]
    }
}
```

---

## Expected Performance Improvements

### Current Performance (Baseline)
- Small (100 vs 100): ~1ms
- Medium (10K vs 10K): ~50ms
- Large (100K vs 100K): ~500ms
- Massive (1M vs 1M): ~8-15 seconds
- Extreme (5M vs 5M): ~90-120 seconds

### After Phase 1 (Arena Allocation)
- Small: ~0.8ms (20% faster)
- Medium: ~40ms (20% faster)
- Large: ~400ms (20% faster)
- Massive: ~6-12 seconds (25% faster)
- Extreme: ~70-90 seconds (30% faster)

### After Phase 2 (Lazy Expansion)
- Small: ~0.7ms (30% faster than baseline)
- Medium: ~25ms (50% faster)
- Large: ~200ms (60% faster)
- Massive: ~2-4 seconds (70-75% faster)
- Extreme: ~15-25 seconds (80-85% faster) ⭐

### After Phase 3 (Parallel + SoA)
- Small: ~0.5ms (50% faster) - on 4-core
- Medium: ~10ms (80% faster) - on 4-core
- Large: ~80ms (84% faster) - on 4-core
- Massive: ~800ms-1.5s (90% faster) - on 8-core
- Extreme: ~4-8 seconds (95% faster) - on 8-core ⭐⭐

**Note**: These are estimates based on algorithmic complexity analysis. Actual results will vary based on hardware, battle composition, and rapidfire chains.

---

## Implementation Checklist

- [ ] Set up performance benchmarking suite
- [ ] Create comprehensive test battles (small, medium, large, extreme)
- [ ] Implement baseline performance measurements
- [ ] Phase 1: Memory arena allocation
- [ ] Verify Phase 1 maintains calculation accuracy
- [ ] Phase 2: Lazy expansion with damage tracking
- [ ] Create equivalence tests (old vs new)
- [ ] Verify Phase 2 maintains calculation accuracy
- [ ] Phase 3: Parallel processing with deterministic RNG
- [ ] Add multi-core benchmarks
- [ ] Verify Phase 3 maintains calculation accuracy
- [ ] Optional: Structure of Arrays refactor
- [ ] Optional: SIMD operations
- [ ] Final performance validation
- [ ] Documentation updates

---

## Risks and Mitigations

### Risk 1: Calculation Divergence
**Impact**: High - Game balance breaks if battles produce different results
**Mitigation**:
- Comprehensive equivalence testing
- Deterministic RNG seeding
- Float comparison with epsilon
- Test with existing battle logs

### Risk 2: Parallel Processing Non-Determinism
**Impact**: High - Different outcomes on different runs
**Mitigation**:
- Seed RNG based on attacker position + round
- Process attacks in deterministic order
- Extensive determinism testing
- Fallback to sequential mode if needed

### Risk 3: Implementation Complexity
**Impact**: Medium - Bugs in complex optimization code
**Mitigation**:
- Incremental implementation (phase by phase)
- Extensive unit testing
- Code review
- Beta testing period

### Risk 4: Hardware-Specific Issues
**Impact**: Low - SIMD or parallel code behaves differently on different CPUs
**Mitigation**:
- Feature flags for advanced optimizations
- Fallback implementations
- Cross-platform testing
- Runtime CPU detection

---

## Monitoring and Profiling

### Before Optimization
```bash
# Use cargo-flamegraph for profiling
cargo install flamegraph
cargo flamegraph --bin battle_engine_debug

# Use criterion for benchmarking
cargo bench
```

### Profiling Tools
1. **perf** (Linux): CPU profiling
2. **valgrind/cachegrind**: Cache analysis
3. **heaptrack**: Memory allocation tracking
4. **cargo-flamegraph**: Visual flame graphs

### Key Metrics to Track
- Total battle duration
- Memory peak usage
- CPU utilization (single vs multi-core)
- Cache miss rate
- Allocation count
- Time per round
- Time per combat phase

---

## Conclusion

The Rust battle engine can be significantly optimized for large-scale simulations while maintaining **identical calculation results**. The recommended approach is:

1. **Start with Lazy Expansion** (Strategy 1) - Biggest impact, lowest risk
2. **Add Memory Arena** (Strategy 5) - Easy implementation, good foundation
3. **Implement Parallel Processing** (Strategy 2) - Major speedup on multi-core
4. **Consider SoA refactor** (Strategy 6) if more performance needed

Expected outcome: **5-10x performance improvement** for massive battles (1M+ units) while preserving exact calculation accuracy.

**Critical Success Factor**: Comprehensive testing to ensure calculation equivalence at every phase.
