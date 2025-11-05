@extends('ingame.layouts.main')

@section('content')
<div id="battlesimulator">
    <div id="page">
        <h2>Battle Simulator</h2>
    </div>

    <div style="display: flex; gap: 20px; margin: 20px 0;">
        <!-- ATTACKER -->
        <div style="flex: 1;">
            <div class="header"><h2>Attacker</h2></div>

            <div style="margin: 20px 0;">
                <h3>Technology</h3>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Weapons: </label>
                    <input type="number" id="attacker_weapon" min="0" max="50" value="0" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Shielding: </label>
                    <input type="number" id="attacker_shield" min="0" max="50" value="0" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Armor: </label>
                    <input type="number" id="attacker_armor" min="0" max="50" value="0" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Hyperspace Technology: </label>
                    <input type="number" id="attacker_hyperspace" min="0" max="50" value="0" style="width: 85px; ">
                    <small style="display: block; color: #888; font-size: 10px;">+5% cargo capacity per level for all ships</small>
                </div>
            </div>

            <div class="header"><h3>Military ships</h3></div>
            <ul id="attacker_military" class="iconsUNUSED">
                @foreach ($attackerShips as $ship)
                <li>
                    <label style="width: 190px; display: inline-block;" for="attacker_{{ $ship->object->machine_name }}">{{ $ship->object->title }}</label>
                    <input
                        type="number"
                        id="attacker_{{ $ship->object->machine_name }}"
                        class="attacker-unit"
                        data-unit="{{ $ship->object->machine_name }}"
                        min="0"
                        value="0"
                        style="width: 85px; ">
                    @if($ship->object->properties->capacity->rawValue > 0)
                    <small style="color: #888; margin-left: 5px; display: block;" class="cargo-display" data-base-cargo="{{ $ship->object->properties->capacity->rawValue }}">
                        Base cargo: {{ number_format($ship->object->properties->capacity->rawValue) }}
                    </small>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>

        <!-- VS -->
        <div style="display: flex; align-items: center; font-size: 32px; font-weight: bold;">
            VS
        </div>

        <!-- DEFENDER -->
        <div style="flex: 1;">
            <div class="header"><h2>Defender</h2></div>

            <div style="margin: 20px 0;">
                <h3>Technology</h3>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Weapons: </label>
                    <input type="number" id="defender_weapon" min="0" max="50" value="0" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Shielding: </label>
                    <input type="number" id="defender_shield" min="0" max="50" value="0" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Armor: </label>
                    <input type="number" id="defender_armor" min="0" max="50" value="0" style="width: 85px; ">
                </div>
            </div>

            <div style="margin: 20px 0;">
                <h3>Resources</h3>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Metal: </label>
                    <input type="number" id="defender_metal" min="0" value="1000000" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Crystal: </label>
                    <input type="number" id="defender_crystal" min="0" value="1000000" style="width: 85px; ">
                </div>
                <div style="margin: 10px 0;">
                    <label style="width: 190px; display: inline-block;">Deuterium: </label>
                    <input type="number" id="defender_deuterium" min="0" value="1000000" style="width: 85px; ">
                </div>
                <small style="display: block; color: #888; font-size: 10px;">Resources available for looting</small>
            </div>

            <div class="header"><h3>Military ships</h3></div>
            <ul id="defender_military" class="iconsUNUSED">
                @foreach ($defenderShips as $ship)
                <li>
                    <label style="width: 190px; display: inline-block;" for="defender_{{ $ship->object->machine_name }}">{{ $ship->object->title }}</label>
                    <input
                        type="number"
                        id="defender_{{ $ship->object->machine_name }}"
                        class="defender-unit"
                        data-unit="{{ $ship->object->machine_name }}"
                        min="0"
                        value="0"
                        style="width: 85px; ">
                    @if($ship->object->properties->capacity->rawValue > 0)
                    <small style="color: #888; margin-left: 5px;" class="cargo-display" data-base-cargo="{{ $ship->object->properties->capacity->rawValue }}">
                        Base cargo: {{ number_format($ship->object->properties->capacity->rawValue) }}
                    </small>
                    @endif
                </li>
                @endforeach
            </ul>

            <div class="header"><h3>Defense</h3></div>
            <ul id="defender_defense" class="iconsUNUSED">
                @foreach ($defense as $defenseUnit)
                <li>
                    <label style="width: 190px; display: inline-block;" for="defender_{{ $defenseUnit->object->machine_name }}">{{ $defenseUnit->object->title }}</label>
                    <input
                        type="number"
                        id="defender_{{ $defenseUnit->object->machine_name }}"
                        class="defender-unit"
                        data-unit="{{ $defenseUnit->object->machine_name }}"
                        min="0"
                        value="0"
                        style="width: 85px; ">
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Buttons -->
    <div style="text-align: center; margin: 30px 0;">
        <button id="simulate-btn" class="btn">Simulate Battle</button>
        <button id="clear-btn" class="btn">Clear All</button>
    </div>

    <!-- Results -->
    <div id="results" style="display: none; margin-top: 30px;">
        <div class="header"><h2>Battle Results</h2></div>
        <div id="results-content"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const simulateBtn = document.getElementById('simulate-btn');
    const clearBtn = document.getElementById('clear-btn');
    const resultsDiv = document.getElementById('results');
    const resultsContent = document.getElementById('results-content');
    const hyperspaceInput = document.getElementById('attacker_hyperspace');

    // Function to update cargo capacity displays based on hyperspace tech
    function updateCargoDisplays() {
        const hyperspaceLevel = parseInt(hyperspaceInput.value) || 0;
        const bonus = 1 + (hyperspaceLevel * 0.05);

        document.querySelectorAll('.cargo-display').forEach(element => {
            const baseCargo = parseInt(element.getAttribute('data-base-cargo'));
            const actualCargo = Math.floor(baseCargo * bonus);

            if (hyperspaceLevel > 0) {
                element.innerHTML = 'Base: ' + baseCargo.toLocaleString() + ' → <span style="color: #0f0;">' + actualCargo.toLocaleString() + '</span>';
            } else {
                element.innerHTML = 'Base cargo: ' + baseCargo.toLocaleString();
            }
        });
    }

    // Update cargo displays when hyperspace tech changes
    hyperspaceInput.addEventListener('input', updateCargoDisplays);

    simulateBtn.addEventListener('click', function() {
        const attackerData = {};
        document.querySelectorAll('.attacker-unit').forEach(input => {
            const amount = parseInt(input.value) || 0;
            if (amount > 0) {
                attackerData[input.dataset.unit] = amount;
            }
        });

        const defenderData = {};
        document.querySelectorAll('.defender-unit').forEach(input => {
            const amount = parseInt(input.value) || 0;
            if (amount > 0) {
                defenderData[input.dataset.unit] = amount;
            }
        });

        const data = {
            attacker: attackerData,
            defender: defenderData,
            attacker_weapon: parseInt(document.getElementById('attacker_weapon').value) || 0,
            attacker_shield: parseInt(document.getElementById('attacker_shield').value) || 0,
            attacker_armor: parseInt(document.getElementById('attacker_armor').value) || 0,
            attacker_hyperspace: parseInt(document.getElementById('attacker_hyperspace').value) || 0,
            defender_weapon: parseInt(document.getElementById('defender_weapon').value) || 0,
            defender_shield: parseInt(document.getElementById('defender_shield').value) || 0,
            defender_armor: parseInt(document.getElementById('defender_armor').value) || 0,
            defender_metal: parseInt(document.getElementById('defender_metal').value) || 0,
            defender_crystal: parseInt(document.getElementById('defender_crystal').value) || 0,
            defender_deuterium: parseInt(document.getElementById('defender_deuterium').value) || 0,
        };

        resultsContent.innerHTML = '<p>Simulating battle...</p>';
        resultsDiv.style.display = 'block';

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
                displayResults(result.result);
            } else {
                resultsContent.innerHTML = '<p>Error simulating battle</p>';
            }
        })
        .catch(error => {
            resultsContent.innerHTML = '<p>Error simulating battle</p>';
            console.error(error);
        });
    });

    clearBtn.addEventListener('click', function() {
        document.querySelectorAll('input[type="number"]').forEach(input => input.value = 0);
        resultsDiv.style.display = 'none';
        updateCargoDisplays(); // Reset cargo displays
    });

    function displayResults(result) {
        let winnerColor = result.winner === 'attacker' ? '#0f0' : (result.winner === 'defender' ? '#f00' : '#ff0');
        let winnerText = result.winner === 'attacker' ? 'Attacker Wins!' : (result.winner === 'defender' ? 'Defender Wins!' : 'Draw!');

        let attackerUnits = '';
        if (result.attacker_units_result.length > 0) {
            attackerUnits = '<h4>Remaining units:</h4><ul>';
            result.attacker_units_result.forEach(u => {
                attackerUnits += '<li>' + u.name + ': ' + Math.floor(u.amount).toLocaleString('en-US') + '</li>';
            });
            attackerUnits += '</ul>';
        }

        let defenderUnits = '';
        if (result.defender_units_result.length > 0) {
            defenderUnits = '<h4>Remaining units:</h4><ul>';
            result.defender_units_result.forEach(u => {
                defenderUnits += '<li>' + u.name + ': ' + Math.floor(u.amount).toLocaleString('en-US') + '</li>';
            });
            defenderUnits += '</ul>';
        }

        let html = '<div style="font-size: 24px; text-align: center; margin: 20px 0; color: ' + winnerColor + '">' +
            winnerText +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">' +
            '<div>' +
            '<h3>Attacker</h3>' +
            '<p>Starting: ' + Math.floor(result.attacker_start).toLocaleString('en-US') + ' units</p>' +
            '<p>Remaining: ' + Math.floor(result.attacker_end).toLocaleString('en-US') + ' units</p>' +
            '<p>Losses: ' + Math.floor(result.attacker_losses).toLocaleString('en-US') + ' units</p>' +
            '<p><strong>Resource Loss:</strong> ' + Math.floor(result.attacker_resource_loss.total).toLocaleString('en-US') + '</p>' +
            '<p style="font-size: 12px; margin-left: 20px;">Metal: ' + Math.floor(result.attacker_resource_loss.metal).toLocaleString('en-US') + '<br>' +
            'Crystal: ' + Math.floor(result.attacker_resource_loss.crystal).toLocaleString('en-US') + '<br>' +
            'Deuterium: ' + Math.floor(result.attacker_resource_loss.deuterium).toLocaleString('en-US') + '</p>' +
            attackerUnits +
            '</div>' +
            '<div>' +
            '<h3>Defender</h3>' +
            '<p>Starting: ' + Math.floor(result.defender_start).toLocaleString('en-US') + ' units</p>' +
            '<p>Remaining: ' + Math.floor(result.defender_end).toLocaleString('en-US') + ' units</p>' +
            '<p>Losses: ' + Math.floor(result.defender_losses).toLocaleString('en-US') + ' units</p>' +
            '<p><strong>Resource Loss:</strong> ' + Math.floor(result.defender_resource_loss.total).toLocaleString('en-US') + '</p>' +
            '<p style="font-size: 12px; margin-left: 20px;">Metal: ' + Math.floor(result.defender_resource_loss.metal).toLocaleString('en-US') + '<br>' +
            'Crystal: ' + Math.floor(result.defender_resource_loss.crystal).toLocaleString('en-US') + '<br>' +
            'Deuterium: ' + Math.floor(result.defender_resource_loss.deuterium).toLocaleString('en-US') + '</p>' +
            defenderUnits +
            '</div>' +
            '</div>' +
            '<div style="margin-top: 30px; padding: 20px; background: #1a1a1a; border-radius: 5px;">' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">' +
            '<div>' +
            '<h3 style="color: #8b4513;">Debris Field</h3>' +
            '<p><strong>Total:</strong> ' + Math.floor(result.debris.total).toLocaleString('en-US') + '</p>' +
            '<p>Metal: ' + Math.floor(result.debris.metal).toLocaleString('en-US') + '</p>' +
            '<p>Crystal: ' + Math.floor(result.debris.crystal).toLocaleString('en-US') + '</p>' +
            '<p>Deuterium: ' + Math.floor(result.debris.deuterium).toLocaleString('en-US') + '</p>' +
            '<p style="margin-top: 10px; color: #00ff00;"><strong>Recyclers needed:</strong> ' + Math.floor(result.recyclers_needed).toLocaleString('en-US') + '</p>' +
            '</div>' +
            '<div>' +
            '<h3 style="color: #ffd700;">Loot</h3>' +
            '<p><strong>Total:</strong> ' + Math.floor(result.loot.total).toLocaleString('en-US') + '</p>' +
            '<p>Metal: ' + Math.floor(result.loot.metal).toLocaleString('en-US') + '</p>' +
            '<p>Crystal: ' + Math.floor(result.loot.crystal).toLocaleString('en-US') + '</p>' +
            '<p>Deuterium: ' + Math.floor(result.loot.deuterium).toLocaleString('en-US') + '</p>' +
            '<p style="margin-top: 10px; color: #00aaff;"><strong>Cargo capacity:</strong> ' + Math.floor(result.attacker_cargo_capacity).toLocaleString('en-US') + '</p>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<p style="text-align: center; margin-top: 20px;">Battle lasted ' + result.rounds + ' rounds</p>' +
            '<p style="text-align: center; color: #888;">Moon chance: ' + result.moon_chance + '%</p>';

        resultsContent.innerHTML = html;
    }
});
</script>
@endsection
