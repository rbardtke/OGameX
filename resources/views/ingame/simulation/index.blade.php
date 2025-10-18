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
                    <label>Weapons: </label>
                    <input type="number" id="attacker_weapon" min="0" max="50" value="0" style="width: 60px;">
                </div>
                <div style="margin: 10px 0;">
                    <label>Shielding: </label>
                    <input type="number" id="attacker_shield" min="0" max="50" value="0" style="width: 60px;">
                </div>
                <div style="margin: 10px 0;">
                    <label>Armor: </label>
                    <input type="number" id="attacker_armor" min="0" max="50" value="0" style="width: 60px;">
                </div>
            </div>

            <div class="header"><h3>Military ships</h3></div>
            <ul id="attacker_military" class="iconsUNUSED">
                @foreach ($ships as $ship)
                <li>
                    <label for="attacker_{{ $ship->object->machine_name }}">{{ $ship->object->title }}</label>
                    <input 
                        type="number" 
                        id="attacker_{{ $ship->object->machine_name }}" 
                        class="attacker-unit"
                        data-unit="{{ $ship->object->machine_name }}"
                        min="0" 
                        value="0">
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
                    <label>Weapons: </label>
                    <input type="number" id="defender_weapon" min="0" max="50" value="0" style="width: 60px;">
                </div>
                <div style="margin: 10px 0;">
                    <label>Shielding: </label>
                    <input type="number" id="defender_shield" min="0" max="50" value="0" style="width: 60px;">
                </div>
                <div style="margin: 10px 0;">
                    <label>Armor: </label>
                    <input type="number" id="defender_armor" min="0" max="50" value="0" style="width: 60px;">
                </div>
            </div>

            <div class="header"><h3>Military ships</h3></div>
            <ul id="defender_military" class="iconsUNUSED">
                @foreach ($ships as $ship)
                <li>
                    <label for="defender_{{ $ship->object->machine_name }}">{{ $ship->object->title }}</label>
                    <input 
                        type="number" 
                        id="defender_{{ $ship->object->machine_name }}" 
                        class="defender-unit"
                        data-unit="{{ $ship->object->machine_name }}"
                        min="0" 
                        value="0">
                </li>
                @endforeach
            </ul>

            <div class="header"><h3>Defense</h3></div>
            <ul id="defender_defense" class="iconsUNUSED">
                @foreach ($defense as $defenseUnit)
                <li>
                    <label for="defender_{{ $defenseUnit->object->machine_name }}">{{ $defenseUnit->object->title }}</label>
                    <input 
                        type="number" 
                        id="defender_{{ $defenseUnit->object->machine_name }}" 
                        class="defender-unit"
                        data-unit="{{ $defenseUnit->object->machine_name }}"
                        min="0" 
                        value="0">
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
            defender_weapon: parseInt(document.getElementById('defender_weapon').value) || 0,
            defender_shield: parseInt(document.getElementById('defender_shield').value) || 0,
            defender_armor: parseInt(document.getElementById('defender_armor').value) || 0,
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
    });

    function displayResults(result) {
        let winnerColor = result.winner === 'attacker' ? '#0f0' : (result.winner === 'defender' ? '#f00' : '#ff0');
        let winnerText = result.winner === 'attacker' ? 'Attacker Wins!' : (result.winner === 'defender' ? 'Defender Wins!' : 'Draw!');
        
        let attackerUnits = '';
        if (result.attacker_units_result.length > 0) {
            attackerUnits = '<h4>Remaining units:</h4><ul>';
            result.attacker_units_result.forEach(u => {
                attackerUnits += '<li>' + u.name + ': ' + u.amount.toLocaleString() + '</li>';
            });
            attackerUnits += '</ul>';
        }

        let defenderUnits = '';
        if (result.defender_units_result.length > 0) {
            defenderUnits = '<h4>Remaining units:</h4><ul>';
            result.defender_units_result.forEach(u => {
                defenderUnits += '<li>' + u.name + ': ' + u.amount.toLocaleString() + '</li>';
            });
            defenderUnits += '</ul>';
        }
        
        let html = '<div style="font-size: 24px; text-align: center; margin: 20px 0; color: ' + winnerColor + '">' +
            winnerText +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">' +
            '<div>' +
            '<h3>Attacker</h3>' +
            '<p>Starting: ' + result.attacker_start.toLocaleString() + '</p>' +
            '<p>Remaining: ' + result.attacker_end.toLocaleString() + '</p>' +
            '<p>Losses: ' + result.attacker_losses.toLocaleString() + '</p>' +
            attackerUnits +
            '</div>' +
            '<div>' +
            '<h3>Defender</h3>' +
            '<p>Starting: ' + result.defender_start.toLocaleString() + '</p>' +
            '<p>Remaining: ' + result.defender_end.toLocaleString() + '</p>' +
            '<p>Losses: ' + result.defender_losses.toLocaleString() + '</p>' +
            defenderUnits +
            '</div>' +
            '</div>' +
            '<p style="text-align: center; margin-top: 20px;">Battle lasted ' + result.rounds + ' rounds</p>';
        
        resultsContent.innerHTML = html;
    }
});
</script>
@endsection
