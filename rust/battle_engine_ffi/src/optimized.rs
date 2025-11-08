//! Optimized battle engine with parallel processing
//!
//! This implementation uses the same expansion logic as the original engine,
//! but parallelizes combat processing across multiple CPU cores for better performance.

use std::collections::HashMap;
use rand::Rng;
use rand::SeedableRng;
use memory_stats::memory_stats;

// Re-export common types from original module
pub use crate::original::{BattleInput, BattleUnitInfo, BattleUnitCount, BattleRound, MemoryMetrics, BattleOutput};

/// Battle unit instance which is used to keep track of individual units and their current health during battle.
#[derive(Clone)]
struct BattleUnitInstance {
    unit_id: i16,
    current_shield_points: f32,
    current_hull_plating: f32,
}

/// Attack action that will be applied to a defender
#[derive(Clone)]
struct AttackAction {
    attacker_idx: usize,
    target_idx: usize,
    damage: f32,
    shield_absorption: f32,
    hull_damage: f32,
    destroyed: bool,
}

/// Process the battle rounds and return the battle output.
pub fn process_battle_rounds(input: BattleInput) -> BattleOutput {
    let mut peak_memory = 0;
    let mut rounds = Vec::new();

    // Create individual ships from provided battle unit info which contains the amount
    let mut attacker_units = expand_units(&input.attacker_units);
    let mut defender_units = expand_units(&input.defender_units);

    // Track peak memory usage for debugging purposes
    update_peak_memory(&mut peak_memory);

    // Fight up to 6 rounds
    for round_idx in 0..6 {
        if attacker_units.is_empty() || defender_units.is_empty() {
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

        // Process combat with parallel processing
        process_combat_parallel(&mut attacker_units, &mut defender_units, &mut round, &input.attacker_units, &input.defender_units, true, round_idx);
        process_combat_parallel(&mut defender_units, &mut attacker_units, &mut round, &input.defender_units, &input.attacker_units, false, round_idx);

        // Cleanup round
        cleanup_round(&mut round, &mut attacker_units, &mut defender_units, &input.attacker_units, &input.defender_units);

        // Update round statistics
        round.attacker_ships = compress_units(&attacker_units);
        round.defender_ships = compress_units(&defender_units);

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

/// Expands unit information into individual unit objects
fn expand_units(units: &HashMap<i16, BattleUnitInfo>) -> Vec<BattleUnitInstance> {
    let mut expanded = Vec::new();

    // Sort by unit_id for deterministic order
    let mut unit_ids: Vec<i16> = units.keys().copied().collect();
    unit_ids.sort();

    for unit_id in unit_ids {
        let unit = units.get(&unit_id).unwrap();
        for _ in 0..unit.amount {
            expanded.push(BattleUnitInstance {
                unit_id: unit.unit_id,
                current_shield_points: unit.shield_points,
                current_hull_plating: unit.hull_plating
            });
        }
    }

    expanded
}

/// Compress individual unit instances into a single unit metadata object
fn compress_units(units: &Vec<BattleUnitInstance>) -> HashMap<i16, BattleUnitCount> {
    units.iter()
        .fold(HashMap::new(), |mut counts, unit| {
            *counts.entry(unit.unit_id).or_insert(0) += 1;
            counts
        })
        .into_iter()
        .map(|(unit_id, count)| {
            (unit_id, BattleUnitCount {
                unit_id,
                amount: count,
            })
        })
        .collect()
}

/// Simulates combat for a single round between two groups of units with parallel processing.
///
/// Uses deterministic RNG seeding to ensure reproducible results across runs.
fn process_combat_parallel(
    attackers: &mut Vec<BattleUnitInstance>,
    defenders: &mut Vec<BattleUnitInstance>,
    round: &mut BattleRound,
    attacker_unit_metadata: &HashMap<i16, BattleUnitInfo>,
    defender_unit_metadata: &HashMap<i16, BattleUnitInfo>,
    is_attacker: bool,
    round_idx: usize,
) {
    use rayon::prelude::*;

    if defenders.is_empty() {
        return;
    }

    // Phase 1: Calculate all attacks in parallel (read-only, deterministic)
    let attack_results: Vec<Vec<AttackAction>> = attackers
        .par_iter()
        .enumerate()
        .map(|(attacker_idx, attacker)| {
            // Each attacker gets deterministic RNG based on its index and round
            let seed = (round_idx as u64 * 1_000_000) + (attacker_idx as u64);
            let mut rng = rand::rngs::StdRng::seed_from_u64(seed);

            let mut actions = Vec::new();
            let mut continue_attacking = true;

            let attacker_metadata = attacker_unit_metadata.get(&attacker.unit_id).unwrap();
            let damage = attacker_metadata.attack_power;

            while continue_attacking && !defenders.is_empty() {
                continue_attacking = false;

                // Select a random defender as a target
                let target_idx = rng.gen_range(0..defenders.len());
                let target = &defenders[target_idx];
                let target_metadata = defender_unit_metadata.get(&target.unit_id).unwrap();

                // Check if the damage is less than 1% of the target's shield points
                if damage < (0.01 * target_metadata.shield_points) {
                    continue;
                }

                // Calculate damage application
                let mut shield_absorption = 0.0;
                let mut new_shield = target.current_shield_points;
                let mut new_hull = target.current_hull_plating;

                if new_shield > 0.0 {
                    if damage <= new_shield {
                        shield_absorption = damage;
                        new_shield -= damage;
                    } else {
                        shield_absorption = new_shield;
                        new_hull -= damage - new_shield;
                        new_shield = 0.0;
                    }
                } else {
                    new_hull -= damage;
                }

                // Check for explosion
                let mut destroyed = false;
                if new_hull / target_metadata.hull_plating < 0.7 {
                    let explosion_chance = 100.0 - ((new_hull / target_metadata.hull_plating) * 100.0);
                    let roll = rng.gen_range(0..=100);
                    if roll < explosion_chance as i32 {
                        new_hull = 0.0;
                        new_shield = 0.0;
                        destroyed = true;
                    }
                }

                actions.push(AttackAction {
                    attacker_idx,
                    target_idx,
                    damage,
                    shield_absorption,
                    hull_damage: target.current_hull_plating - new_hull,
                    destroyed,
                });

                // Check for rapidfire
                continue_attacking = if let Some(rapidfire_amount) = attacker_metadata.rapidfire.get(&target.unit_id) {
                    let chance = 100.0 / *rapidfire_amount as f64;
                    let rounded_chance = (chance * 100.0).floor() / 100.0;
                    let rapidfire_chance = 100.0 - rounded_chance;
                    let roll = rng.gen_range(0.0..100.0);
                    roll <= rapidfire_chance
                } else {
                    false
                };
            }

            actions
        })
        .collect();

    // Phase 2: Apply all attacks sequentially (maintains determinism)
    for actions in attack_results {
        for action in actions {
            if action.target_idx < defenders.len() {
                let target = &mut defenders[action.target_idx];

                // Apply shield damage
                if action.shield_absorption > 0.0 {
                    target.current_shield_points -= action.shield_absorption;
                    if target.current_shield_points < 0.0 {
                        target.current_shield_points = 0.0;
                    }
                }

                // Apply hull damage
                target.current_hull_plating -= action.hull_damage;
                if action.destroyed {
                    target.current_hull_plating = 0.0;
                    target.current_shield_points = 0.0;
                }

                // Update round statistics
                if is_attacker {
                    round.hits_attacker += 1;
                    round.full_strength_attacker += action.damage as f64;
                    round.absorbed_damage_defender += action.shield_absorption as f64;
                } else {
                    round.hits_defender += 1;
                    round.full_strength_defender += action.damage as f64;
                    round.absorbed_damage_attacker += action.shield_absorption as f64;
                }
            }
        }
    }
}

/// Clean up the round after all units have attacked each other
fn cleanup_round(
    round: &mut BattleRound,
    attackers: &mut Vec<BattleUnitInstance>,
    defenders: &mut Vec<BattleUnitInstance>,
    units_metadata_attacker: &HashMap<i16, BattleUnitInfo>,
    units_metadata_defender: &HashMap<i16, BattleUnitInfo>,
) {
    // Remove destroyed attacker units
    attackers.retain(|unit| {
        if unit.current_hull_plating <= 0.0 {
            increment_battle_unit_count_amount(&mut round.attacker_losses_in_round, unit.unit_id, 1);
            return false;
        }
        true
    });

    // Regenerate shields for attackers
    for unit in attackers.iter_mut() {
        let unit_metadata = units_metadata_attacker.get(&unit.unit_id).unwrap();
        unit.current_shield_points = unit_metadata.shield_points;
    }

    // Remove destroyed defender units
    defenders.retain(|unit| {
        if unit.current_hull_plating <= 0.0 {
            increment_battle_unit_count_amount(&mut round.defender_losses_in_round, unit.unit_id, 1);
            return false;
        }
        true
    });

    // Regenerate shields for defenders
    for unit in defenders.iter_mut() {
        let unit_metadata = units_metadata_defender.get(&unit.unit_id).unwrap();
        unit.current_shield_points = unit_metadata.shield_points;
    }
}

/// Calculate the losses for the attacker and defender
fn calculate_losses(
    round: &mut BattleRound,
    initial_attacker: &HashMap<i16, BattleUnitInfo>,
    initial_defender: &HashMap<i16, BattleUnitInfo>,
) {
    for (_, unit) in initial_attacker {
        let initial_count = unit.amount;
        let current_count = round.attacker_ships.get(&unit.unit_id).map(|unit| unit.amount).unwrap_or(0);

        if current_count < initial_count {
            let loss_amount = initial_count - current_count;
            increment_battle_unit_count_amount(&mut round.attacker_losses, unit.unit_id, loss_amount);
        }
    }

    for (_, unit) in initial_defender {
        let initial_count = unit.amount;
        let current_count = round.defender_ships.get(&unit.unit_id).map(|unit| unit.amount).unwrap_or(0);

        if current_count < initial_count {
            let loss_amount = initial_count - current_count;
            increment_battle_unit_count_amount(&mut round.defender_losses, unit.unit_id, loss_amount);
        }
    }
}

/// Helper method to increment the amount property of a BattleUnitCount struct
fn increment_battle_unit_count_amount(hash_map: &mut HashMap<i16, BattleUnitCount>, unit_id: i16, amount_to_increment: u32) {
    let count = hash_map.entry(unit_id).or_insert(BattleUnitCount {
        unit_id,
        amount: 0,
    });
    count.amount += amount_to_increment;
}

/// Update the peak memory usage statistics
fn update_peak_memory(current_peak: &mut u64) {
    if let Some(usage) = memory_stats() {
        *current_peak = (*current_peak).max(usage.physical_mem as u64 / 1024);
    }
}
