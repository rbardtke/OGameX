<?php

namespace OGame\Services;

use OGame\Models\Resources;

/**
 * Service to encode/decode espionage report API codes.
 *
 * API Code Format: sr-{universe}-{hash}
 * Example: sr-en-256-265b38b75e565e12526a560bf4f5c83bfce4c5c5
 *
 * - sr = espionage report
 * - universe = server identifier (e.g., "en-256")
 * - hash = encoded data containing resources, fleets, defense, research
 */
class EspionageApiCodeService
{
    /**
     * Generate API code from espionage report data.
     *
     * @param Resources $resources
     * @param array<string, int> $ships
     * @param array<string, int> $defense
     * @param array<string, int> $research
     * @param string $universe Universe identifier (e.g., "en-256")
     * @return string
     */
    public function generateApiCode(
        Resources $resources,
        array $ships,
        array $defense,
        array $research,
        string $universe
    ): string {
        $data = [
            'resources' => [
                'metal' => $resources->metal->get(),
                'crystal' => $resources->crystal->get(),
                'deuterium' => $resources->deuterium->get(),
            ],
            'ships' => $ships,
            'defense' => $defense,
            'research' => $research,
        ];

        // Encode data as JSON and then create a hash
        $jsonData = json_encode($data);
        $hash = hash('sha256', $jsonData);

        // Store the mapping for decoding (in cache for 24 hours)
        cache()->put('espionage_api_' . $hash, $jsonData, now()->addHours(24));

        return "sr-{$universe}-{$hash}";
    }

    /**
     * Parse API code and return decoded data.
     *
     * @param string $apiCode
     * @return array<string, mixed>|null
     */
    public function parseApiCode(string $apiCode): ?array
    {
        // Expected format: sr-{universe}-{hash}
        $parts = explode('-', $apiCode);

        // Validate format (should have at least 4 parts: sr, universe1, universe2, hash)
        if (count($parts) < 4 || $parts[0] !== 'sr') {
            return null;
        }

        // Extract hash (last part)
        $hash = end($parts);

        // Try to retrieve data from cache
        $jsonData = cache()->get('espionage_api_' . $hash);

        if (!$jsonData) {
            return null;
        }

        $data = json_decode($jsonData, true);

        if (!$data) {
            return null;
        }

        return $data;
    }

    /**
     * Extract tech levels from research data.
     *
     * @param array<string, int> $research
     * @return array{weapon: int, shield: int, armor: int, hyperspace: int}
     */
    public function extractTechLevels(array $research): array
    {
        return [
            'weapon' => $research['weapon_technology'] ?? 0,
            'shield' => $research['shielding_technology'] ?? 0,
            'armor' => $research['armor_technology'] ?? 0,
            'hyperspace' => $research['hyperspace_technology'] ?? 0,
        ];
    }
}
