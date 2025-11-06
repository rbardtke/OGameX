//! # Battle Engine FFI
//!
//! `battle_engine_ffi` is the Rust implementation of the OGameX battle engine.
//!
//! This Rust library is called from the PHP client RustBattleEngine.php via FFI (Foreign Function Interface)
//! and takes the battle input in JSON, processes the battle rounds and returns the battle output in JSON.
//!
//! This library now contains TWO implementations:
//! - `original`: The original implementation (up to 200x faster than PHP)
//! - `optimized`: New lazy expansion implementation (additional 5-10x improvement for large battles)

use std::ffi::{CStr, CString};
use std::os::raw::c_char;

pub mod original;
pub mod optimized;

// Re-export common types
pub use original::{BattleInput, BattleOutput};

/// FFI interface to process the battle rounds using the ORIGINAL engine.
///
/// This is the default engine that PHP currently uses.
#[no_mangle]
pub extern "C" fn fight_battle_rounds(input_json: *const c_char) -> *mut c_char {
    let input_str = unsafe { CStr::from_ptr(input_json).to_str().unwrap() };
    let battle_input: BattleInput = serde_json::from_str(input_str).unwrap();
    let battle_output = original::process_battle_rounds(battle_input);
    let result_json = serde_json::to_string(&battle_output).unwrap();
    let c_str = CString::new(result_json).unwrap();
    c_str.into_raw()
}

/// FFI interface to process the battle rounds using the OPTIMIZED engine.
///
/// This uses lazy expansion for improved performance on large battles.
#[no_mangle]
pub extern "C" fn fight_battle_rounds_optimized(input_json: *const c_char) -> *mut c_char {
    let input_str = unsafe { CStr::from_ptr(input_json).to_str().unwrap() };
    let battle_input: BattleInput = serde_json::from_str(input_str).unwrap();
    let battle_output = optimized::process_battle_rounds(battle_input);
    let result_json = serde_json::to_string(&battle_output).unwrap();
    let c_str = CString::new(result_json).unwrap();
    c_str.into_raw()
}

/// FFI interface to run both engines and compare results.
///
/// Returns JSON with both results and comparison data.
/// Format: {"original": {...}, "optimized": {...}, "comparison": {...}}
#[no_mangle]
pub extern "C" fn fight_battle_rounds_compare(input_json: *const c_char) -> *mut c_char {
    use std::time::Instant;

    let input_str = unsafe { CStr::from_ptr(input_json).to_str().unwrap() };
    let battle_input: BattleInput = serde_json::from_str(input_str).unwrap();

    // Run original engine
    let start_original = Instant::now();
    let original_output = original::process_battle_rounds(battle_input.clone());
    let duration_original = start_original.elapsed();

    // Run optimized engine
    let start_optimized = Instant::now();
    let optimized_output = optimized::process_battle_rounds(battle_input);
    let duration_optimized = start_optimized.elapsed();

    // Compare results
    let comparison = serde_json::json!({
        "original": {
            "output": original_output,
            "duration_ms": duration_original.as_millis(),
            "duration_us": duration_original.as_micros(),
        },
        "optimized": {
            "output": optimized_output,
            "duration_ms": duration_optimized.as_millis(),
            "duration_us": duration_optimized.as_micros(),
        },
        "performance": {
            "speedup_factor": duration_original.as_micros() as f64 / duration_optimized.as_micros() as f64,
            "time_saved_ms": duration_original.as_millis() as i64 - duration_optimized.as_millis() as i64,
            "original_faster": duration_original < duration_optimized,
        }
    });

    let result_json = serde_json::to_string(&comparison).unwrap();
    let c_str = CString::new(result_json).unwrap();
    c_str.into_raw()
}
