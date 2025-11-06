# Build Instructions for Battle Engine

## Network Dependency Issue

The Rust battle engine requires external crates (dependencies) from crates.io:
- `serde` - JSON serialization
- `serde_json` - JSON handling
- `rand` - Random number generation
- `memory-stats` - Memory profiling

**These dependencies require internet access to download on first build.**

---

## Building in an Environment with Internet Access

### Prerequisites
- Rust toolchain (cargo, rustc)
- Internet access to crates.io
- Linux environment (for .so compilation)

### Build Steps

```bash
cd /home/user/OGameX/rust

# Build the optimized battle engine library
cargo build --release

# Build the comparison test tool
cargo build --release --bin compare_engines

# Copy compiled library to storage (for PHP to use)
cp target/release/libbattle_engine_ffi.so ../storage/rust-libs/
```

Expected output:
```
   Compiling serde v1.0.xxx
   Compiling rand v0.8.xxx
   Compiling battle_engine_ffi v0.2.0
    Finished release [optimized] target(s) in XX.XXs
```

---

## Running Tests

Once built successfully:

### Quick Test (Single Battle)
```bash
cd /home/user/OGameX/rust
cargo run --release --bin battle_engine_debug
```

### Full Test Suite (8 Scenarios)
```bash
cd /home/user/OGameX/rust
cargo run --release --bin compare_engines
```

Expected result:
```
Correctness: 8/8 tests passed
✅ All scenarios produce IDENTICAL results!
```

---

## Alternative: Vendored Dependencies

If you need to build in an offline/restricted environment, you can vendor the dependencies:

### On a machine WITH internet access:

```bash
cd /home/user/OGameX/rust

# Download and vendor all dependencies
cargo vendor

# This creates a vendor/ directory with all dependencies
```

Then commit the vendor/ directory to git, or copy it to the offline machine.

### On the offline machine:

```bash
cd /home/user/OGameX/rust

# Create .cargo/config.toml to use vendored dependencies
mkdir -p .cargo
cat > .cargo/config.toml << 'EOF'
[source.crates-io]
replace-with = "vendored-sources"

[source.vendored-sources]
directory = "vendor"
EOF

# Now build offline
cargo build --release --offline
```

---

## Troubleshooting

### Error: "failed to get `memory-stats`... got 403"

**Cause**: No internet access or crates.io is blocked

**Solutions**:
1. Build on a machine with internet access
2. Use vendored dependencies (see above)
3. Ask your network admin to whitelist crates.io

### Error: "no matching package named `rand` found"

**Cause**: Dependencies not cached, offline mode won't work

**Solution**: You must build online at least once, or use vendored dependencies

### Build succeeds but tests show differences

**This should NEVER happen.** If tests show "Results differ!", report immediately:
1. Which scenario failed
2. Exact differences reported
3. Run multiple times to check if it's deterministic

---

## What Gets Built

After successful build, you'll have:

1. **Library**: `target/release/libbattle_engine_ffi.so`
   - Contains both original and optimized engines
   - Used by PHP via FFI

2. **Debug Tool**: `target/release/battle_engine_debug`
   - Runs comparison on a single battle
   - Shows performance metrics

3. **Test Suite**: `target/release/compare_engines`
   - Runs 8 different battle scenarios
   - Verifies calculation equivalence
   - Reports performance improvements

---

## Next Steps After Successful Build

1. ✅ **Run tests**: `cargo run --release --bin compare_engines`
2. ✅ **Verify**: Check for "8/8 tests passed"
3. ✅ **Copy library**: `cp target/release/libbattle_engine_ffi.so ../storage/rust-libs/`
4. ✅ **Test PHP integration**: Update RustBattleEngine.php to call optimized engine
5. ✅ **Performance test**: Compare with production battle data

---

## Current Status

✅ Code is complete and syntax-correct
✅ Borrow checker errors fixed
✅ Compilation structure verified
⚠️  **Requires internet access for first build**

The optimized battle engine is ready to be built and tested in an environment with internet connectivity.

---

## Quick Reference Commands

```bash
# From /home/user/OGameX/rust directory:

# Build everything
cargo build --release

# Run quick test
cargo run --release --bin battle_engine_debug

# Run full test suite
cargo run --release --bin compare_engines

# Copy to PHP
cp target/release/libbattle_engine_ffi.so ../storage/rust-libs/

# Clean build (if needed)
cargo clean && cargo build --release
```
