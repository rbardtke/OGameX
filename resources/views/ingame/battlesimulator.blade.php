@extends('ingame.layouts.minimal')

@section('content')
<style>
    body {
        background: #000 url("/img/bg/background-large.jpg") no-repeat 50% 0;
        margin: 0;
        padding: 0;
        font-family: Verdana, Arial, sans-serif;
        font-size: 11px;
        color: #6f9fc8;
    }

    #content {
        display: flex;
        align-items: center;
        height: 100%;
    }

    #battlesimulator {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
        padding: 10px;
        max-width: 1600px;
        margin: 0 auto;
    }

    input[type=number],
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        -moz-appearance: textfield;
        margin: 0;
    }

    .panel {
        background: rgba(8, 28, 56, 0.9);
        border: 1px solid #395f8b;
        border-radius: 5px;
        padding: 0;
        position: relative;
    }

    .panel-header {
        background: linear-gradient(to bottom, #1e4976 0%, #16354f 100%);
        border-bottom: 1px solid #395f8b;
        padding: 8px 12px;
        font-weight: bold;
        font-size: 12px;
        color: #6f9fc8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 5px 5px 0 0;
    }

    .panel-content {
        padding: 10px;
    }

    .section {
        margin-bottom: 15px;
    }

    .section-title {
        font-weight: bold;
        color: #6f9fc8;
        margin-bottom: 8px;
        font-size: 11px;
        padding-bottom: 3px;
        border-bottom: 1px solid #2d5172;
    }

    .input-row {
        display: flex;
        align-items: center;
        margin-bottom: 4px;
        position: relative;
    }

    .input-row label {
        flex: 1;
        color: #6f9fc8;
        font-size: 11px;
    }

    .input-row input[type="number"] {
        width: 60px;
        padding: 2px 4px;
        background: #0d1014;
        border: 1px solid #395f8b;
        color: #6f9fc8;
        text-align: right;
        font-size: 11px;
    }

    .unit-loss {
        display: none;
        margin-left: 8px;
        margin-top: 0;
        font-size: 10px;
        font-weight: bold;
        white-space: nowrap;
    }

    .unit-loss.visible {
        display: inline-block;
    }

    .unit-loss.lost {
        color: #f00;
    }

    .unit-loss.remaining {
        color: #0f0;
    }

    .unit-loss.destroyed {
        color: #ff6666;
    }

    .input-row input[type="number"]:focus {
        outline: none;
        border-color: #6f9fc8;
    }

    .btn {
        background: linear-gradient(to bottom, #3d6fa0 0%, #2d4f70 100%);
        border: 1px solid #5a88b5;
        color: #fff;
        padding: 8px 16px;
        cursor: pointer;
        font-size: 11px;
        font-weight: bold;
        border-radius: 3px;
        width: 100%;
        margin-bottom: 10px;
    }

    .btn:hover {
        background: linear-gradient(to bottom, #4d7fb0 0%, #3d5f80 100%);
    }

    .btn-simulate {
        background: linear-gradient(to bottom, #5a9747 0%, #3a6730 100%);
        font-size: 13px;
        padding: 10px;
        width: 100%;
        color: #fff;
        border: 1px solid #5a88b5;
        padding: 8px 16px;
        cursor: pointer;
        font-size: 11px;
        font-weight: bold;
        border-radius: 3px;
        position: absolute;
        bottom: 0;
    }

    .btn-simulate:hover {
        background: linear-gradient(to bottom, #6aa757 0%, #4a7740 100%);
    }

    .resource-input {
        background: rgba(0, 0, 0, 0.3);
        padding: 8px;
        border-radius: 3px;
        margin-bottom: 10px;
    }

    .result-box {
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid #395f8b;
        border-radius: 3px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .result-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 11px;
    }

    .result-label {
        color: #6f9fc8;
    }

    .result-value {
        color: #fff;
        font-weight: bold;
    }

    .winner-text {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 3px;
    }

    .winner-attacker {
        background: rgba(0, 255, 0, 0.2);
        color: #0f0;
        border: 1px solid #0f0;
    }

    .winner-defender {
        background: rgba(255, 0, 0, 0.2);
        color: #f00;
        border: 1px solid #f00;
    }

    .winner-draw {
        background: rgba(255, 255, 0, 0.2);
        color: #ff0;
        border: 1px solid #ff0;
    }

    .losses-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 10px;
    }

    .loss-item {
        background: rgba(0, 0, 0, 0.3);
        padding: 8px;
        border-radius: 3px;
        border: 1px solid #2d5172;
    }

    .loss-title {
        font-weight: bold;
        color: #6f9fc8;
        margin-bottom: 5px;
        font-size: 10px;
    }

    .loss-value {
        font-size: 13px;
        color: #f00;
        font-weight: bold;
    }

    .profit-value {
        font-size: 13px;
        color: #0f0;
        font-weight: bold;
    }

    .debris-section, .loot-section {
        background: rgba(0, 0, 0, 0.3);
        padding: 10px;
        border-radius: 3px;
        margin-bottom: 10px;
    }

    .resource-line {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3px;
        font-size: 11px;
    }

    .clear-btn {
        background: linear-gradient(to bottom, #a03d3d 0%, #702d2d 100%);
        border-color: #b55a5a;
        padding: 5px;
        font-size: 10px;
    }

    .clear-btn:hover {
        background: linear-gradient(to bottom, #b04d4d 0%, #803d3d 100%);
    }

    .api-load-btn {
        background: transparent;
        border: 1px solid #395f8b;
        color: #6f9fc8;
        padding: 5px;
        font-size: 10px;
        cursor: pointer;
        border-radius: 3px;
    }

    .api-load-btn:hover {
        background: rgba(63, 143, 185, 0.2);
    }

    .empty-result {
        text-align: center;
        color: #6f9fc8;
        opacity: 0.5;
        padding: 40px 20px;
        font-style: italic;
    }
</style>
<div style="display: block; width: 100%;">
    <h2 style="text-align: center; font-size: 20px;">Battle Simulator</h2>
    <div id="battlesimulator">
        
        <!-- LEFT COLUMN: Attackers -->
        <div class="panel">
            <div class="panel-header">
                <span>⚔️ Attackers</span>
                <button class="clear-btn" onclick="clearAttacker()">Clear</button>
            </div>
            <div class="panel-content">
                <!-- Planet Selection -->
                <div class="section">
                    <div class="section-title">Load from Planet</div>
                    <select id="planet-selector" style="width: 100%; padding: 4px; background: #0d1014; border: 1px solid #395f8b; color: #6f9fc8; font-size: 11px; margin-bottom: 5px;">
                        <option value="">-- Select a planet --</option>
                        @foreach ($userPlanets ?? [] as $planet)
                            <option value="{{ $planet['id'] }}">{{ $planet['name'] }} [{{ $planet['coordinates'] }}]</option>
                        @endforeach
                    </select>
                    <small style="color: #6f9fc8; opacity: 0.7; font-size: 9px;">Loads coordinates, fleet, and tech from selected planet</small>
                </div>

                <!-- Coordinates -->
                <div class="section">
                    <div class="section-title">Coordinates</div>
                    <div class="input-row">
                        <label>Galaxy:</label>
                        <input type="number" id="attacker_galaxy" min="1" max="9" value="1">
                    </div>
                    <div class="input-row">
                        <label>System:</label>
                        <input type="number" id="attacker_system" min="1" max="499" value="1">
                    </div>
                    <div class="input-row">
                        <label>Position:</label>
                        <input type="number" id="attacker_position" min="1" max="15" value="1">
                    </div>
                </div>

                <!-- Technology -->
                <div class="section">
                    <div class="section-title">Combat</div>
                    <div class="input-row">
                        <label>Weapon:</label>
                        <input type="number" id="attacker_weapon" min="0" max="50" value="0">
                    </div>
                    <div class="input-row">
                        <label>Shield:</label>
                        <input type="number" id="attacker_shield" min="0" max="50" value="0">
                    </div>
                    <div class="input-row">
                        <label>Armour:</label>
                        <input type="number" id="attacker_armor" min="0" max="50" value="0">
                    </div>
                </div>

                <!-- Drives -->
                <div class="section">
                    <div class="section-title">Drives</div>
                    <div class="input-row">
                        <label>Combustion:</label>
                        <input type="number" id="attacker_combustion" min="0" max="50" value="0">
                    </div>
                    <div class="input-row">
                        <label>Impulse:</label>
                        <input type="number" id="attacker_impulse" min="0" max="50" value="0">
                    </div>
                    <div class="input-row">
                        <label>Hyperspace:</label>
                        <input type="number" id="attacker_hyperspace" min="0" max="50" value="0">
                    </div>
                    <small style="color: #6f9fc8; opacity: 0.7; font-size: 9px;">+5% cargo per level (hyperspace)</small>
                </div>

                <!-- Ships -->
                <div class="section">
                    <div class="section-title">Ships</div>
                    <div class="unit-list">
                        @foreach ($attackerShips as $ship)
                        <div class="input-row">
                            <label title="{{ $ship->object->title }}">{{ $ship->object->title }}</label>
                            <span class="unit-loss" id="attacker_loss_{{ $ship->object->machine_name }}"></span>
                            <input type="number" id="attacker_{{ $ship->object->machine_name }}" class="attacker-unit" data-unit="{{ $ship->object->machine_name }}" min="0" value="0">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Defenders Panel -->
        <div class="panel">
            <div class="panel-header">
                <span>🛡️ Defenders planet/moon</span>
                <button class="clear-btn" onclick="clearDefender()">Clear</button>
            </div>
            <div class="panel-content">

                <!-- Coordinates -->
                <div class="section">
                    <div class="section-title">Coordinates</div>
                    <div class="input-row">
                        <label>Galaxy:</label>
                        <input type="number" id="defender_galaxy" min="1" max="9" value="1">
                    </div>
                    <div class="input-row">
                        <label>System:</label>
                        <input type="number" id="defender_system" min="1" max="499" value="1">
                    </div>
                    <div class="input-row">
                        <label>Position:</label>
                        <input type="number" id="defender_position" min="1" max="15" value="1">
                    </div>
                </div>

                <!-- Combat Technology -->
                <div class="section">
                    <div class="section-title">Combat</div>
                    <div class="input-row">
                        <label>Weapon:</label>
                        <input type="number" id="defender_weapon" min="0" max="50" value="0">
                    </div>
                    <div class="input-row">
                        <label>Shield:</label>
                        <input type="number" id="defender_shield" min="0" max="50" value="0">
                    </div>
                    <div class="input-row">
                        <label>Armour:</label>
                        <input type="number" id="defender_armor" min="0" max="50" value="0">
                    </div>
                </div>

                <!-- Resources -->
                <div class="section">
                    <div class="section-title">Resources</div>
                    <div class="resource-input">
                        <div class="input-row">
                            <label>Metal:</label>
                            <input type="number" id="defender_metal" min="0" value="0">
                        </div>
                        <div class="input-row">
                            <label>Crystal:</label>
                            <input type="number" id="defender_crystal" min="0" value="0">
                        </div>
                        <div class="input-row">
                            <label>Deuterium:</label>
                            <input type="number" id="defender_deuterium" min="0" value="0">
                        </div>
                    </div>
                </div>

                <!-- Ships -->
                <div class="section">
                    <div class="section-title">Ships</div>
                    <div class="unit-list">
                        @foreach ($defenderShips as $ship)
                        <div class="input-row">
                            <label title="{{ $ship->object->title }}">{{ $ship->object->title }}</label>
                            <span class="unit-loss" id="defender_loss_{{ $ship->object->machine_name }}"></span>
                            <input type="number" id="defender_{{ $ship->object->machine_name }}" class="defender-unit" data-unit="{{ $ship->object->machine_name }}" min="0" value="0">
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Defense -->
                <div class="section">
                    <div class="section-title">Defence</div>
                    <div class="unit-list">
                        @foreach ($defense as $defenseUnit)
                        <div class="input-row">
                            <label title="{{ $defenseUnit->object->title }}">{{ $defenseUnit->object->title }}</label>
                            <span class="unit-loss" id="defender_loss_{{ $defenseUnit->object->machine_name }}"></span>
                            <input type="number" id="defender_{{ $defenseUnit->object->machine_name }}" class="defender-unit" data-unit="{{ $defenseUnit->object->machine_name }}" min="0" value="0">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Results -->
        <div class="panel">
            <div class="panel-header">📊 Result</div>
            <div class="panel-content" id="results-panel">
                <div class="empty-result">
                    Run a simulation to see results
                </div>
            </div>
            <!-- Simulate Button -->
            <button class="btn-simulate" id="simulate-btn">Simulate</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const simulateBtn = document.getElementById('simulate-btn');
    const resultsPanel = document.getElementById('results-panel');
    const planetSelector = document.getElementById('planet-selector');

    // Handle planet selection
    planetSelector.addEventListener('change', function() {
        const planetId = this.value;

        if (!planetId) {
            return;
        }

        // Fetch planet data via AJAX
        fetch('/battlesimulator/planet-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ planet_id: planetId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;

                // Set coordinates
                document.getElementById('attacker_galaxy').value = data.coordinates.galaxy;
                document.getElementById('attacker_system').value = data.coordinates.system;
                document.getElementById('attacker_position').value = data.coordinates.position;

                // Set technologies
                document.getElementById('attacker_weapon').value = data.technologies.weapon_technology;
                document.getElementById('attacker_shield').value = data.technologies.shielding_technology;
                document.getElementById('attacker_armor').value = data.technologies.armor_technology;
                document.getElementById('attacker_combustion').value = data.technologies.combustion_drive;
                document.getElementById('attacker_impulse').value = data.technologies.impulse_drive;
                document.getElementById('attacker_hyperspace').value = data.technologies.hyperspace_drive;

                // Set fleet
                // First clear all fleet inputs
                document.querySelectorAll('.attacker-unit').forEach(input => {
                    input.value = 0;
                });

                // Then set the fleet from planet
                Object.keys(data.fleet).forEach(unitName => {
                    const input = document.getElementById('attacker_' + unitName);
                    if (input) {
                        input.value = data.fleet[unitName];
                    }
                });
            } else {
                alert('Error loading planet data: ' + (result.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching planet data:', error);
            alert('Error loading planet data');
        });
    });

    // Prefill data from espionage report API code if provided
    @if($prefillData)
    const prefillData = @json($prefillData);

    // Prefill defender coordinates
    if (prefillData.coordinates) {
        document.getElementById('defender_galaxy').value = prefillData.coordinates.galaxy || 1;
        document.getElementById('defender_system').value = prefillData.coordinates.system || 1;
        document.getElementById('defender_position').value = prefillData.coordinates.position || 1;
    }

    // Prefill defender resources
    if (prefillData.resources) {
        document.getElementById('defender_metal').value = prefillData.resources.metal || 0;
        document.getElementById('defender_crystal').value = prefillData.resources.crystal || 0;
        document.getElementById('defender_deuterium').value = prefillData.resources.deuterium || 0;
    }

    // Prefill defender ships
    if (prefillData.ships) {
        Object.keys(prefillData.ships).forEach(shipName => {
            const input = document.getElementById('defender_' + shipName);
            if (input) input.value = prefillData.ships[shipName];
        });
    }

    // Prefill defender defense
    if (prefillData.defense) {
        Object.keys(prefillData.defense).forEach(defenseName => {
            const input = document.getElementById('defender_' + defenseName);
            if (input) input.value = prefillData.defense[defenseName];
        });
    }

    // Prefill defender tech from research
    if (prefillData.research) {
        document.getElementById('defender_weapon').value = prefillData.research.weapon_technology || 0;
        document.getElementById('defender_shield').value = prefillData.research.shielding_technology || 0;
        document.getElementById('defender_armor').value = prefillData.research.armor_technology || 0;
    }
    @endif

    simulateBtn.addEventListener('click', function() {
        // Clear previous loss displays
        document.querySelectorAll('.unit-loss').forEach(el => {
            el.classList.remove('visible', 'lost', 'remaining', 'destroyed');
            el.textContent = '';
        });

        // Store initial unit counts
        const attackerInitial = {};
        document.querySelectorAll('.attacker-unit').forEach(input => {
            const amount = parseInt(input.value) || 0;
            const machineName = input.dataset.unit;
            attackerInitial[machineName] = amount;
        });

        const defenderInitial = {};
        document.querySelectorAll('.defender-unit').forEach(input => {
            const amount = parseInt(input.value) || 0;
            const machineName = input.dataset.unit;
            defenderInitial[machineName] = amount;
        });

        const attackerData = {};
        Object.keys(attackerInitial).forEach(key => {
            if (attackerInitial[key] > 0) attackerData[key] = attackerInitial[key];
        });

        const defenderData = {};
        Object.keys(defenderInitial).forEach(key => {
            if (defenderInitial[key] > 0) defenderData[key] = defenderInitial[key];
        });

        const data = {
            attacker: attackerData,
            defender: defenderData,
            attacker_galaxy: parseInt(document.getElementById('attacker_galaxy').value) || 1,
            attacker_system: parseInt(document.getElementById('attacker_system').value) || 1,
            attacker_position: parseInt(document.getElementById('attacker_position').value) || 1,
            attacker_weapon: parseInt(document.getElementById('attacker_weapon').value) || 0,
            attacker_shield: parseInt(document.getElementById('attacker_shield').value) || 0,
            attacker_armor: parseInt(document.getElementById('attacker_armor').value) || 0,
            attacker_combustion: parseInt(document.getElementById('attacker_combustion').value) || 0,
            attacker_impulse: parseInt(document.getElementById('attacker_impulse').value) || 0,
            attacker_hyperspace: parseInt(document.getElementById('attacker_hyperspace').value) || 0,
            defender_galaxy: parseInt(document.getElementById('defender_galaxy').value) || 1,
            defender_system: parseInt(document.getElementById('defender_system').value) || 1,
            defender_position: parseInt(document.getElementById('defender_position').value) || 1,
            defender_weapon: parseInt(document.getElementById('defender_weapon').value) || 0,
            defender_shield: parseInt(document.getElementById('defender_shield').value) || 0,
            defender_armor: parseInt(document.getElementById('defender_armor').value) || 0,
            defender_metal: parseInt(document.getElementById('defender_metal').value) || 0,
            defender_crystal: parseInt(document.getElementById('defender_crystal').value) || 0,
            defender_deuterium: parseInt(document.getElementById('defender_deuterium').value) || 0,
        };

        resultsPanel.innerHTML = '<div style="text-align: center; padding: 20px;">⏳ Simulating battle...</div>';

        fetch('/battlesimulator/simulate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayResults(result.result, attackerInitial, defenderInitial);
            } else {
                resultsPanel.innerHTML = '<div style="color: #f00; text-align: center; padding: 20px;">⚠️ Error simulating battle</div>';
            }
        })
        .catch(error => {
            resultsPanel.innerHTML = '<div style="color: #f00; text-align: center; padding: 20px;">⚠️ Error: ' + error.message + '</div>';
            console.error(error);
        });
    });

    function formatTravelTime(seconds) {
        if (seconds === 0) {
            return '0s';
        }

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        let parts = [];
        if (hours > 0) parts.push(hours + 'h');
        if (minutes > 0) parts.push(minutes + 'm');
        if (secs > 0) parts.push(secs + 's');

        return parts.join(' ');
    }

    function displayResults(result, attackerInitial, defenderInitial) {
        // Create title-to-machine-name mapping
        const titleToMachine = {};
        document.querySelectorAll('.attacker-unit, .defender-unit').forEach(input => {
            const label = input.closest('.input-row').querySelector('label');
            const title = label.textContent.trim();
            const machineName = input.dataset.unit;
            titleToMachine[title] = machineName;
        });

        // Update attacker loss displays
        Object.keys(attackerInitial).forEach(machineName => {
            const initial = attackerInitial[machineName];
            if (initial > 0) {
                // Find remaining from results
                const unitInput = document.getElementById('attacker_' + machineName);
                const label = unitInput.closest('.input-row').querySelector('label');
                const title = label.textContent.trim();

                let remaining = 0;
                if (result.attacker_units_result) {
                    const resultUnit = result.attacker_units_result.find(u => u.name === title);
                    if (resultUnit) {
                        remaining = Math.floor(resultUnit.amount);
                    }
                }

                const lost = initial - remaining;
                const lossDisplay = document.getElementById('attacker_loss_' + machineName);

                if (remaining === 0) {
                    lossDisplay.textContent = '(-' + lost.toLocaleString() + ') 💀';
                    lossDisplay.classList.add('visible', 'destroyed');
                } else if (lost > 0) {
                    lossDisplay.textContent = '(-' + lost.toLocaleString() + ') → ' + remaining.toLocaleString();
                    lossDisplay.classList.add('visible', 'lost');
                } else {
                    lossDisplay.textContent = '✓ ' + remaining.toLocaleString();
                    lossDisplay.classList.add('visible', 'remaining');
                }
            }
        });

        // Update defender loss displays
        Object.keys(defenderInitial).forEach(machineName => {
            const initial = defenderInitial[machineName];
            if (initial > 0) {
                const unitInput = document.getElementById('defender_' + machineName);
                const label = unitInput.closest('.input-row').querySelector('label');
                const title = label.textContent.trim();

                let remaining = 0;
                if (result.defender_units_result) {
                    const resultUnit = result.defender_units_result.find(u => u.name === title);
                    if (resultUnit) {
                        remaining = Math.floor(resultUnit.amount);
                    }
                }

                const lost = initial - remaining;
                const lossDisplay = document.getElementById('defender_loss_' + machineName);

                if (remaining === 0) {
                    lossDisplay.textContent = '(-' + lost.toLocaleString() + ') 💀';
                    lossDisplay.classList.add('visible', 'destroyed');
                } else if (lost > 0) {
                    lossDisplay.textContent = '(-' + lost.toLocaleString() + ') → ' + remaining.toLocaleString();
                    lossDisplay.classList.add('visible', 'lost');
                } else {
                    lossDisplay.textContent = '✓ ' + remaining.toLocaleString();
                    lossDisplay.classList.add('visible', 'remaining');
                }
            }
        });

        let winnerClass = result.winner === 'attacker' ? 'winner-attacker' : (result.winner === 'defender' ? 'winner-defender' : 'winner-draw');
        let winnerText = result.winner === 'attacker' ? '⚔️ Attackers win' : (result.winner === 'defender' ? '🛡️ Defenders win' : '⚖️ Draw');

        let html = `
            <div class="winner-text ${winnerClass}">${winnerText}</div>

            <div class="result-box">
                <div class="result-row">
                    <span class="result-label">Rounds:</span>
                    <span class="result-value">${result.rounds}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Travel time:</span>
                    <span class="result-value">${formatTravelTime(result.travel_time_seconds)}</span>
                </div>
            </div>

            <div class="losses-grid">
                <div class="loss-item">
                    <div class="loss-title">Attackers</div>
                    <div class="loss-value">${Math.floor(result.attacker_losses).toLocaleString()}</div>
                </div>
                <div class="loss-item">
                    <div class="loss-title">Defenders</div>
                    <div class="loss-value">${Math.floor(result.defender_losses).toLocaleString()}</div>
                </div>
            </div>

            <div class="debris-section">
                <div style="font-weight: bold; color: #8b4513; margin-bottom: 5px;">💎 Debris remaining</div>
                <div class="resource-line">
                    <span>Metal:</span>
                    <span>${Math.floor(result.debris.metal).toLocaleString()}</span>
                </div>
                <div class="resource-line">
                    <span>Crystal:</span>
                    <span>${Math.floor(result.debris.crystal).toLocaleString()}</span>
                </div>
                <div class="resource-line">
                    <span>Deuterium:</span>
                    <span>${Math.floor(result.debris.deuterium).toLocaleString()}</span>
                </div>
                <div class="resource-line" style="border-top: 1px solid #395f8b; padding-top: 5px; margin-top: 5px; font-weight: bold;">
                    <span>Total:</span>
                    <span>${Math.floor(result.debris.total).toLocaleString()}</span>
                </div>
                <div class="resource-line" style="margin-top: 8px; color: #0f0;">
                    <span>Recycler(s):</span>
                    <span>${Math.floor(result.recyclers_needed).toLocaleString()}</span>
                </div>
            </div>

            <div class="loot-section">
                <div style="font-weight: bold; color: #ffd700; margin-bottom: 5px;">💰 Plunder</div>
                <div class="resource-line">
                    <span>Metal:</span>
                    <span>${Math.floor(result.loot.metal).toLocaleString()}</span>
                </div>
                <div class="resource-line">
                    <span>Crystal:</span>
                    <span>${Math.floor(result.loot.crystal).toLocaleString()}</span>
                </div>
                <div class="resource-line">
                    <span>Deuterium:</span>
                    <span>${Math.floor(result.loot.deuterium).toLocaleString()}</span>
                </div>
                <div class="resource-line" style="border-top: 1px solid #395f8b; padding-top: 5px; margin-top: 5px; font-weight: bold;">
                    <span>Total:</span>
                    <span>${Math.floor(result.loot.total).toLocaleString()}</span>
                </div>
            </div>

            <div class="result-box">
                <div class="result-row">
                    <span class="result-label">Cargo capacity:</span>
                    <span class="result-value">${Math.floor(result.attacker_cargo_capacity).toLocaleString()}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Moon chance:</span>
                    <span class="result-value">${result.moon_chance}%</span>
                </div>
            </div>

            <div class="result-box">
                <div style="font-weight: bold; margin-bottom: 8px; color: #6f9fc8;">Profit *</div>
                <div class="losses-grid">
                    <div class="loss-item">
                        <div class="loss-title">Attackers</div>
                        <div class="${(result.loot.total + result.debris.total - result.attacker_resource_loss.total) >= 0 ? 'profit-value' : 'loss-value'}">
                            ${Math.floor(result.loot.total + result.debris.total - result.attacker_resource_loss.total).toLocaleString()}
                        </div>
                    </div>
                    <div class="loss-item">
                        <div class="loss-title">Defenders</div>
                        <div class="loss-value">
                            ${Math.floor(-result.defender_resource_loss.total - result.loot.total).toLocaleString()}
                        </div>
                    </div>
                </div>
            </div>
        `;

        resultsPanel.innerHTML = html;
    }
});

function clearAttacker() {
    document.querySelectorAll('.attacker-unit').forEach(input => {
        input.value = 0;
        const machineName = input.dataset.unit;
        const lossDisplay = document.getElementById('attacker_loss_' + machineName);
        if (lossDisplay) {
            lossDisplay.classList.remove('visible', 'lost', 'remaining', 'destroyed');
            lossDisplay.textContent = '';
        }
    });
    document.getElementById('planet-selector').value = '';
    document.getElementById('attacker_galaxy').value = 1;
    document.getElementById('attacker_system').value = 1;
    document.getElementById('attacker_position').value = 1;
    document.getElementById('attacker_weapon').value = 0;
    document.getElementById('attacker_shield').value = 0;
    document.getElementById('attacker_armor').value = 0;
    document.getElementById('attacker_combustion').value = 0;
    document.getElementById('attacker_impulse').value = 0;
    document.getElementById('attacker_hyperspace').value = 0;
}

function clearDefender() {
    document.querySelectorAll('.defender-unit').forEach(input => {
        input.value = 0;
        const machineName = input.dataset.unit;
        const lossDisplay = document.getElementById('defender_loss_' + machineName);
        if (lossDisplay) {
            lossDisplay.classList.remove('visible', 'lost', 'remaining', 'destroyed');
            lossDisplay.textContent = '';
        }
    });
    document.getElementById('defender_galaxy').value = 1;
    document.getElementById('defender_system').value = 1;
    document.getElementById('defender_position').value = 1;
    document.getElementById('defender_weapon').value = 0;
    document.getElementById('defender_shield').value = 0;
    document.getElementById('defender_armor').value = 0;
    document.getElementById('defender_metal').value = 0;
    document.getElementById('defender_crystal').value = 0;
    document.getElementById('defender_deuterium').value = 0;
}
</script>
@endsection
