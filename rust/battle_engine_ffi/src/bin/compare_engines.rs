//! Comprehensive comparison tool for original vs optimized battle engines
//!
//! This binary runs both engines with various battle scenarios and provides detailed
//! comparison reports including:
//! - Performance metrics (execution time, memory usage)
//! - Result equivalence verification
//! - Detailed diff reporting for any discrepancies

use battle_engine_ffi::{BattleInput, original, optimized};
use std::time::Instant;
use std::collections::HashMap;
use serde_json;

#[derive(Debug)]
struct ComparisonResult {
    scenario_name: String,
    original_duration_ms: f64,
    optimized_duration_ms: f64,
    speedup_factor: f64,
    results_match: bool,
    differences: Vec<String>,
    original_peak_memory_kb: u64,
    optimized_peak_memory_kb: u64,
    total_units: usize,
}

impl ComparisonResult {
    fn print_summary(&self) {
        println!("\n{'═':<80}", "");
        println!("Scenario: {}", self.scenario_name);
        println!("{'─':<80}", "");
        println!("Total units: {}", self.total_units);
        println!("\nPerformance:");
        println!("  Original:  {:>10.2} ms  (Memory: {} KB)", self.original_duration_ms, self.original_peak_memory_kb);
        println!("  Optimized: {:>10.2} ms  (Memory: {} KB)", self.optimized_duration_ms, self.optimized_peak_memory_kb);
        println!("  Speedup:   {:>10.2}x", self.speedup_factor);

        if self.optimized_duration_ms < self.original_duration_ms {
            let improvement = ((self.original_duration_ms - self.optimized_duration_ms) / self.original_duration_ms) * 100.0;
            println!("  Improvement: {:.1}% faster", improvement);
        } else {
            let regression = ((self.optimized_duration_ms - self.original_duration_ms) / self.original_duration_ms) * 100.0;
            println!("  ⚠️  Regression: {:.1}% slower", regression);
        }

        let memory_saved = self.original_peak_memory_kb as i64 - self.optimized_peak_memory_kb as i64;
        if memory_saved > 0 {
            let memory_improvement = (memory_saved as f64 / self.original_peak_memory_kb as f64) * 100.0;
            println!("  Memory saved: {} KB ({:.1}%)", memory_saved, memory_improvement);
        }

        println!("\nCorrectness:");
        if self.results_match {
            println!("  ✅ Results match perfectly!");
        } else {
            println!("  ❌ Results differ!");
            for diff in &self.differences {
                println!("    - {}", diff);
            }
        }
    }
}

fn main() {
    println!("╔{'═':<78}╗", "");
    println!("║{:^78}║", "Battle Engine Comparison Suite");
    println!("║{:^78}║", "Original vs Optimized Implementation");
    println!("╚{'═':<78}╝", "");

    let scenarios = vec![
        create_small_battle_scenario(),
        create_medium_battle_scenario(),
        create_large_battle_scenario(),
        create_very_large_battle_scenario(),
        create_massive_battle_scenario(),
        create_unbalanced_scenario(),
        create_rapidfire_heavy_scenario(),
        create_high_shield_scenario(),
    ];

    let mut results = Vec::new();

    for (name, input) in scenarios {
        println!("\n\nRunning scenario: {}", name);
        println!("Please wait...");
        let result = compare_engines(&name, input);
        result.print_summary();
        results.push(result);
    }

    print_overall_summary(&results);
}

fn compare_engines(scenario_name: &str, input: BattleInput) -> ComparisonResult {
    let total_units: usize = input.attacker_units.values().map(|u| u.amount as usize).sum::<usize>()
        + input.defender_units.values().map(|u| u.amount as usize).sum();

    // Run original engine
    let start = Instant::now();
    let original_output = original::process_battle_rounds(input.clone());
    let original_duration = start.elapsed();

    // Run optimized engine
    let start = Instant::now();
    let optimized_output = optimized::process_battle_rounds(input);
    let optimized_duration = start.elapsed();

    // Compare results
    let (results_match, differences) = compare_outputs(&original_output, &optimized_output);

    ComparisonResult {
        scenario_name: scenario_name.to_string(),
        original_duration_ms: original_duration.as_secs_f64() * 1000.0,
        optimized_duration_ms: optimized_duration.as_secs_f64() * 1000.0,
        speedup_factor: original_duration.as_secs_f64() / optimized_duration.as_secs_f64(),
        results_match,
        differences,
        original_peak_memory_kb: original_output.memory_metrics.peak_memory,
        optimized_peak_memory_kb: optimized_output.memory_metrics.peak_memory,
        total_units,
    }
}

fn compare_outputs(
    original: &battle_engine_ffi::original::BattleOutput,
    optimized: &battle_engine_ffi::original::BattleOutput,
) -> (bool, Vec<String>) {
    let mut differences = Vec::new();

    // Compare number of rounds
    if original.rounds.len() != optimized.rounds.len() {
        differences.push(format!(
            "Different number of rounds: original={}, optimized={}",
            original.rounds.len(),
            optimized.rounds.len()
        ));
        return (false, differences);
    }

    // Compare each round
    for (i, (orig_round, opt_round)) in original.rounds.iter().zip(optimized.rounds.iter()).enumerate() {
        let round_num = i + 1;

        // Compare ship counts
        if !compare_unit_counts(&orig_round.attacker_ships, &opt_round.attacker_ships) {
            differences.push(format!("Round {}: Attacker ships differ", round_num));
        }
        if !compare_unit_counts(&orig_round.defender_ships, &opt_round.defender_ships) {
            differences.push(format!("Round {}: Defender ships differ", round_num));
        }

        // Compare losses
        if !compare_unit_counts(&orig_round.attacker_losses, &opt_round.attacker_losses) {
            differences.push(format!("Round {}: Attacker losses differ", round_num));
        }
        if !compare_unit_counts(&orig_round.defender_losses, &opt_round.defender_losses) {
            differences.push(format!("Round {}: Defender losses differ", round_num));
        }

        // Compare statistics (with tolerance for floating point)
        const EPSILON: f64 = 0.01; // Allow small floating point differences

        if (orig_round.absorbed_damage_attacker - opt_round.absorbed_damage_attacker).abs() > EPSILON {
            differences.push(format!(
                "Round {}: Absorbed damage attacker differs: {:.2} vs {:.2}",
                round_num, orig_round.absorbed_damage_attacker, opt_round.absorbed_damage_attacker
            ));
        }

        if (orig_round.absorbed_damage_defender - opt_round.absorbed_damage_defender).abs() > EPSILON {
            differences.push(format!(
                "Round {}: Absorbed damage defender differs: {:.2} vs {:.2}",
                round_num, orig_round.absorbed_damage_defender, opt_round.absorbed_damage_defender
            ));
        }

        if orig_round.hits_attacker != opt_round.hits_attacker {
            differences.push(format!(
                "Round {}: Attacker hits differ: {} vs {}",
                round_num, orig_round.hits_attacker, opt_round.hits_attacker
            ));
        }

        if orig_round.hits_defender != opt_round.hits_defender {
            differences.push(format!(
                "Round {}: Defender hits differ: {} vs {}",
                round_num, orig_round.hits_defender, opt_round.hits_defender
            ));
        }
    }

    (differences.is_empty(), differences)
}

fn compare_unit_counts(
    orig: &HashMap<i16, battle_engine_ffi::original::BattleUnitCount>,
    opt: &HashMap<i16, battle_engine_ffi::original::BattleUnitCount>,
) -> bool {
    if orig.len() != opt.len() {
        return false;
    }

    for (unit_id, orig_count) in orig {
        if let Some(opt_count) = opt.get(unit_id) {
            if orig_count.amount != opt_count.amount {
                return false;
            }
        } else {
            return false;
        }
    }

    true
}

fn print_overall_summary(results: &[ComparisonResult]) {
    println!("\n\n");
    println!("╔{'═':<78}╗", "");
    println!("║{:^78}║", "Overall Summary");
    println!("╚{'═':<78}╝", "");

    let all_match = results.iter().all(|r| r.results_match);
    let total_tests = results.len();
    let passed_tests = results.iter().filter(|r| r.results_match).count();

    println!("\nCorrectness: {}/{} tests passed", passed_tests, total_tests);
    if all_match {
        println!("✅ All scenarios produce IDENTICAL results!");
    } else {
        println!("❌ Some scenarios have differences!");
    }

    println!("\nPerformance Summary:");
    println!("{:<30} {:>12} {:>12} {:>10}", "Scenario", "Original", "Optimized", "Speedup");
    println!("{:─<70}", "");

    for result in results {
        println!(
            "{:<30} {:>10.2}ms {:>10.2}ms {:>9.2}x",
            result.scenario_name,
            result.original_duration_ms,
            result.optimized_duration_ms,
            result.speedup_factor
        );
    }

    let avg_speedup: f64 = results.iter().map(|r| r.speedup_factor).sum::<f64>() / results.len() as f64;
    println!("{:─<70}", "");
    println!("{:<30} {:>32} {:>9.2}x", "Average Speedup", "", avg_speedup);

    // Find best and worst cases
    let best = results.iter().max_by(|a, b| a.speedup_factor.partial_cmp(&b.speedup_factor).unwrap()).unwrap();
    let worst = results.iter().min_by(|a, b| a.speedup_factor.partial_cmp(&b.speedup_factor).unwrap()).unwrap();

    println!("\nBest case:  {} ({:.2}x speedup)", best.scenario_name, best.speedup_factor);
    println!("Worst case: {} ({:.2}x speedup)", worst.scenario_name, worst.speedup_factor);

    // Memory summary
    let total_memory_saved: i64 = results.iter()
        .map(|r| r.original_peak_memory_kb as i64 - r.optimized_peak_memory_kb as i64)
        .sum();

    if total_memory_saved > 0 {
        println!("\n✅ Total memory saved across all tests: {} KB", total_memory_saved);
    }
}

// Battle scenario creators
fn create_small_battle_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "204": {"unit_id": 204, "amount": 100, "shield_points": 10, "attack_power": 50, "hull_plating": 400, "rapidfire": {"210": 5, "212": 5}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 100, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}}
        }
    }"#;
    ("Small (100v100)".to_string(), serde_json::from_str(json).unwrap())
}

fn create_medium_battle_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "204": {"unit_id": 204, "amount": 5000, "shield_points": 10, "attack_power": 50, "hull_plating": 400, "rapidfire": {"210": 5, "212": 5}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 5000, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}}
        }
    }"#;
    ("Medium (5Kv5K)".to_string(), serde_json::from_str(json).unwrap())
}

fn create_large_battle_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "204": {"unit_id": 204, "amount": 50000, "shield_points": 10, "attack_power": 50, "hull_plating": 400, "rapidfire": {"210": 5, "212": 5}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 50000, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}}
        }
    }"#;
    ("Large (50Kv50K)".to_string(), serde_json::from_str(json).unwrap())
}

fn create_very_large_battle_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "204": {"unit_id": 204, "amount": 200000, "shield_points": 10, "attack_power": 50, "hull_plating": 400, "rapidfire": {"210": 5, "212": 5}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 200000, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}}
        }
    }"#;
    ("Very Large (200Kv200K)".to_string(), serde_json::from_str(json).unwrap())
}

fn create_massive_battle_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "204": {"unit_id": 204, "amount": 500000, "shield_points": 10, "attack_power": 50, "hull_plating": 400, "rapidfire": {"210": 5, "212": 5}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 500000, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}}
        }
    }"#;
    ("Massive (500Kv500K)".to_string(), serde_json::from_str(json).unwrap())
}

fn create_unbalanced_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "206": {"unit_id": 206, "amount": 10000, "shield_points": 50, "attack_power": 400, "hull_plating": 2700, "rapidfire": {"401": 10}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 50000, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}}
        }
    }"#;
    ("Unbalanced (10K destroyers v 50K rockets)".to_string(), serde_json::from_str(json).unwrap())
}

fn create_rapidfire_heavy_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "206": {"unit_id": 206, "amount": 5000, "shield_points": 50, "attack_power": 400, "hull_plating": 2700, "rapidfire": {"210": 5, "212": 5, "401": 10}}
        },
        "defender_units": {
            "401": {"unit_id": 401, "amount": 25000, "shield_points": 20, "attack_power": 80, "hull_plating": 200, "rapidfire": {}},
            "210": {"unit_id": 210, "amount": 1000, "shield_points": 200, "attack_power": 150, "hull_plating": 800, "rapidfire": {}}
        }
    }"#;
    ("Rapidfire Heavy".to_string(), serde_json::from_str(json).unwrap())
}

fn create_high_shield_scenario() -> (String, BattleInput) {
    let json = r#"{
        "attacker_units": {
            "204": {"unit_id": 204, "amount": 10000, "shield_points": 10, "attack_power": 50, "hull_plating": 400, "rapidfire": {}}
        },
        "defender_units": {
            "408": {"unit_id": 408, "amount": 100, "shield_points": 10000, "attack_power": 1000, "hull_plating": 10000, "rapidfire": {}}
        }
    }"#;
    ("High Shield (weak v strong)".to_string(), serde_json::from_str(json).unwrap())
}
