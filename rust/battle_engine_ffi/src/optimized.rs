//! Optimized battle engine implementation
//!
//! This implementation uses lazy expansion - units are kept in compressed groups
//! and only expanded into individual instances when they take unique damage.
//! This dramatically reduces memory usage and improves performance for large battles.

use std::collections::HashMap;
use rand::Rng;
use memory_stats::memory_stats;

// Re-export common types from original module
pub use crate::original::{BattleInput, BattleUnitInfo, BattleUnitCount, BattleRound, MemoryMetrics, BattleOutput};

/// Battle unit instance which is used to keep track of individual units with unique health.
#[derive(Clone, Debug)]
struct BattleUnitInstance {
    unit_id: i16,
    current_shield_points: f32,
    current_hull_plating: f32,
}

/// A group of units of the same type, tracking both pristine and damaged units.
#[derive(Clone, Debug)]
struct BattleUnitGroup {
    unit_id: i16,
    /// Units at full health (compressed representation)
    full_health_count: u32,
    /// Units with unique damage states (expanded representation)
    damaged_instances: Vec<BattleUnitInstance>,
    /// Base stats for creating new instances
    base_shield: f32,
    base_hull: f32,
    base_attack: f32,
    rapidfire: HashMap<i16, u16>,
}

impl BattleUnitGroup {
    fn new(info: &BattleUnitInfo) -> Self {
        Self {
            unit_id: info.unit_id,
            full_health_count: info.amount,
            damaged_instances: Vec::new(),
            base_shield: info.shield_points,
            base_hull: info.hull_plating,
            base_attack: info.attack_power,
            rapidfire: info.rapidfire.clone(),
        }
    }

    /// Total number of units in this group (pristine + damaged)
    fn total_count(&self) -> usize {
        self.full_health_count as usize + self.damaged_instances.len()
    }

    /// Get or create a damaged instance at the given local index
    fn get_or_damage_unit(&mut self, local_idx: usize) -> &mut BattleUnitInstance {
        if local_idx < self.full_health_count as usize {
            // Need to "expand" this pristine unit
            self.full_health_count -= 1;
            self.damaged_instances.push(BattleUnitInstance {
                unit_id: self.unit_id,
                current_shield_points: self.base_shield,
                current_hull_plating: self.base_hull,
            });
            self.damaged_instances.last_mut().unwrap()
        } else {
            let damaged_idx = local_idx - self.full_health_count as usize;
            &mut self.damaged_instances[damaged_idx]
        }
    }

    /// Remove destroyed units from damaged instances
    fn remove_destroyed(&mut self, losses_map: &mut HashMap<i16, BattleUnitCount>) {
        self.damaged_instances.retain(|unit| {
            if unit.current_hull_plating <= 0.0 {
                increment_battle_unit_count_amount(losses_map, unit.unit_id, 1);
                false
            } else {
                true
            }
        });
    }

    /// Regenerate shields for all damaged units
    fn regenerate_shields(&mut self) {
        for unit in &mut self.damaged_instances {
            unit.current_shield_points = self.base_shield;
        }
    }
}

/// A collection of unit groups representing one side of the battle
#[derive(Clone, Debug)]
struct BattleSide {
    groups: HashMap<i16, BattleUnitGroup>,
    total_units: usize,
}

impl BattleSide {
    fn new(units: &HashMap<i16, BattleUnitInfo>) -> Self {
        let mut groups = HashMap::new();
        let mut total = 0;

        for (unit_id, info) in units {
            groups.insert(*unit_id, BattleUnitGroup::new(info));
            total += info.amount as usize;
        }

        Self {
            groups,
            total_units: total,
        }
    }

    fn is_empty(&self) -> bool {
        self.total_units == 0
    }

    /// Select a random unit and return mutable reference to it along with its metadata
    /// Returns: (unit_id, mutable unit reference, base_shield, base_hull)
    fn select_random_unit(&mut self, rng: &mut impl Rng) -> Option<(i16, &mut BattleUnitInstance, f32, f32)> {
        if self.total_units == 0 {
            return None;
        }

        let target_idx = rng.gen_range(0..self.total_units);
        let mut cumulative = 0;

        // First pass: find which group and local index, and get metadata
        // IMPORTANT: Sort by unit_id to ensure deterministic order (HashMap iteration is random)
        let mut target_info: Option<(i16, usize, f32, f32)> = None;

        let mut unit_ids: Vec<i16> = self.groups.keys().copied().collect();
        unit_ids.sort();

        for unit_id in unit_ids {
            let group = self.groups.get(&unit_id).unwrap();
            let group_size = group.total_count();
            if target_idx < cumulative + group_size {
                let local_idx = target_idx - cumulative;
                target_info = Some((unit_id, local_idx, group.base_shield, group.base_hull));
                break;
            }
            cumulative += group_size;
        }

        // Second pass: get mutable reference
        if let Some((unit_id, local_idx, base_shield, base_hull)) = target_info {
            let group = self.groups.get_mut(&unit_id).unwrap();
            let unit = group.get_or_damage_unit(local_idx);
            Some((unit_id, unit, base_shield, base_hull))
        } else {
            None
        }
    }

    /// Remove all destroyed units and update total count
    fn cleanup_destroyed(&mut self, losses_map: &mut HashMap<i16, BattleUnitCount>) {
        for group in self.groups.values_mut() {
            group.remove_destroyed(losses_map);
        }
        self.recalculate_total();
    }

    /// Regenerate all shields
    fn regenerate_shields(&mut self) {
        for group in self.groups.values_mut() {
            group.regenerate_shields();
        }
    }

    /// Recalculate total unit count
    fn recalculate_total(&mut self) {
        self.total_units = self.groups.values()
            .map(|g| g.total_count())
            .sum();
    }

    /// Compress to output format
    fn compress(&self) -> HashMap<i16, BattleUnitCount> {
        self.groups.iter()
            .filter(|(_, group)| group.total_count() > 0)
            .map(|(unit_id, group)| {
                (*unit_id, BattleUnitCount {
                    unit_id: *unit_id,
                    amount: group.total_count() as u32,
                })
            })
            .collect()
    }
}

/// Process the battle rounds and return the battle output.
pub fn process_battle_rounds(input: BattleInput) -> BattleOutput {
    let mut peak_memory = 0;
    let mut rounds = Vec::new();

    // Create unit groups (lazy expansion)
    let mut attacker_side = BattleSide::new(&input.attacker_units);
    let mut defender_side = BattleSide::new(&input.defender_units);

    // Track peak memory usage for debugging purposes
    update_peak_memory(&mut peak_memory);

    // Fight up to 6 rounds
    for _ in 0..6 {
        if attacker_side.is_empty() || defender_side.is_empty() {
            break;
        }

        let mut round = BattleRound {
            attacker_ships: HashMap::new(),
            defender_ships: HashMap::new(),
            attacker_losses: HashMap::new(),
            defender_losses: HashMap::new(),
            attacker_losses_in_round: HashMap::new(),
            defender_losses_in_round: HashMap::new(),
            absorbed_damage_attacker: 0.0,
            absorbed_damage_defender: 0.0,
            full_strength_attacker: 0.0,
            full_strength_defender: 0.0,
            hits_attacker: 0,
            hits_defender: 0,
        };

        // Process combat
        process_combat(&mut attacker_side, &mut defender_side, &mut round, true);
        process_combat(&mut defender_side, &mut attacker_side, &mut round, false);

        // Cleanup round
        cleanup_round(&mut round, &mut attacker_side, &mut defender_side);

        // Update round statistics
        round.attacker_ships = attacker_side.compress();
        round.defender_ships = defender_side.compress();

        // Calculate accumulated losses
        calculate_losses(&mut round, &input.attacker_units, &input.defender_units);

        rounds.push(round);

        // Track peak memory usage for debugging purposes
        update_peak_memory(&mut peak_memory);
    }

    BattleOutput {
        rounds,
        memory_metrics: MemoryMetrics {
            peak_memory,
        },
    }
}

/// Simulates combat for a single round between two sides.
fn process_combat(
    attackers: &mut BattleSide,
    defenders: &mut BattleSide,
    round: &mut BattleRound,
    is_attacker: bool,
) {
    let mut rng = rand::thread_rng();

    // We need to iterate over all attackers
    // Build a list of all units to attack with (unit_id, count)
    // IMPORTANT: Sort by unit_id to ensure deterministic order (HashMap iteration is random)
    let mut attacking_units: Vec<(i16, usize, f32, HashMap<i16, u16>)> = attackers.groups.iter()
        .map(|(unit_id, group)| {
            (*unit_id, group.total_count(), group.base_attack, group.rapidfire.clone())
        })
        .collect();
    attacking_units.sort_by_key(|&(unit_id, _, _, _)| unit_id);

    for (_attacker_unit_id, count, attack_power, rapidfire) in attacking_units {
        for _ in 0..count {
            let mut continue_attacking = true;
            let damage = attack_power;

            while continue_attacking {
                continue_attacking = false;

                // Select a random defender as a target
                // Now returns: (unit_id, mutable unit, base_shield, base_hull)
                if let Some((target_unit_id, target, target_base_shield, target_base_hull)) = defenders.select_random_unit(&mut rng) {
                    // Check if the damage is less than 1% of the target's shield points. If so,
                    // attack is negated.
                    if damage < (0.01 * target_base_shield) {
                        continue;
                    }

                    // Apply damage to shields first, then hull plating
                    let mut shield_absorption = 0.0;
                    if target.current_shield_points > 0.0 {
                        if damage <= target.current_shield_points {
                            shield_absorption = damage;
                            target.current_shield_points -= damage;
                        } else {
                            shield_absorption = target.current_shield_points;
                            target.current_hull_plating -= damage - target.current_shield_points;
                            target.current_shield_points = 0.0;
                        }
                    } else {
                        target.current_hull_plating -= damage;
                    }

                    // If hull integrity < 70%, then unit can explode randomly. Roll dice to see if it does.
                    if target.current_hull_plating / target_base_hull < 0.7 {
                        let explosion_chance = 100.0 - ((target.current_hull_plating / target_base_hull) * 100.0);
                        let roll = rng.gen_range(0..=100);
                        if roll < explosion_chance as i32 {
                            // Unit explodes, set current hull plating and shield points to 0.
                            target.current_hull_plating = 0.0;
                            target.current_shield_points = 0.0;
                        }
                    }

                    // Update round statistics for hits and damage absorbed
                    if is_attacker {
                        round.hits_attacker += 1;
                        round.full_strength_attacker += damage as f64;
                        round.absorbed_damage_defender += shield_absorption as f64;
                    } else {
                        round.hits_defender += 1;
                        round.full_strength_defender += damage as f64;
                        round.absorbed_damage_attacker += shield_absorption as f64;
                    }

                    // Check if the current unit has rapidfire against the target unit. If so, then
                    // roll dice to see if the current unit can attack again.
                    continue_attacking = if let Some(rapidfire_amount) = rapidfire.get(&target_unit_id) {
                        // Rapidfire chance is calculated as 100 - (100 / amount). For example:
                        // - rapidfire amount of 4 means 100 - (100 / 4) = 75% chance.
                        // - rapidfire amount of 10 means 100 - (100 / 10) = 90% chance.
                        // - rapidfire amount of 33 means 100 - (100 / 33) = 96.97%
                        let chance = 100.0 / *rapidfire_amount as f64;
                        let rounded_chance = (chance * 100.0).floor() / 100.0;
                        let rapidfire_chance = 100.0 - rounded_chance;

                        // Roll for rapidfire
                        let roll = rng.gen_range(0.0..100.0);

                        // If the roll is less than or equal to the rapidfire chance, the unit can attack again
                        // and continue_attacking is set to true which will cause the loop to continue.
                        roll <= rapidfire_chance
                    } else {
                        false
                    };
                } else {
                    // No defenders left
                    break;
                }
            }
        }
    }
}

/// Clean up the round after all units have attacked each other.
fn cleanup_round(
    round: &mut BattleRound,
    attackers: &mut BattleSide,
    defenders: &mut BattleSide,
) {
    // Remove destroyed units
    attackers.cleanup_destroyed(&mut round.attacker_losses_in_round);
    defenders.cleanup_destroyed(&mut round.defender_losses_in_round);

    // Regenerate shields
    attackers.regenerate_shields();
    defenders.regenerate_shields();
}

/// Calculate the losses for the attacker and defender in this round compared to the starting
/// units before the battle.
fn calculate_losses(
    round: &mut BattleRound,
    initial_attacker: &HashMap<i16, BattleUnitInfo>,
    initial_defender: &HashMap<i16, BattleUnitInfo>,
) {
    // Calculate losses by comparing current counts with initial counts
    for (_, unit) in initial_attacker {
        let initial_count = unit.amount;
        let current_count = round.attacker_ships.get(&unit.unit_id).map(|unit| unit.amount).unwrap_or(0);

        if current_count < initial_count {
            let loss_amount = initial_count - current_count;
            increment_battle_unit_count_amount(&mut round.attacker_losses, unit.unit_id, loss_amount);
        }
    }

    // Do the same for defender
    for (_, unit) in initial_defender {
        let initial_count = unit.amount;
        let current_count = round.defender_ships.get(&unit.unit_id).map(|unit| unit.amount).unwrap_or(0);

        if current_count < initial_count {
            let loss_amount = initial_count - current_count;
            increment_battle_unit_count_amount(&mut round.defender_losses, unit.unit_id, loss_amount);
        }
    }
}

/// Helper method to increment the amount property of a BattleUnitCount struct.
fn increment_battle_unit_count_amount(hash_map: &mut HashMap<i16, BattleUnitCount>, unit_id: i16, amount_to_increment: u32) {
    let count = hash_map.entry(unit_id).or_insert(BattleUnitCount {
        unit_id,
        amount: 0,
    });
    count.amount += amount_to_increment;
}

/// Update the peak memory usage statistics. Only used for debugging purposes.
fn update_peak_memory(current_peak: &mut u64) {
    if let Some(usage) = memory_stats() {
        *current_peak = (*current_peak).max(usage.physical_mem as u64 / 1024);
    }
}
