<?php

    if (!function_exists("mobile_flat_matches_visible_for_flats")) {
        function mobile_flat_matches_visible_for_flats($flat, string $visibleForFlats): bool
        {
            $flat = trim((string)$flat);

            if ($flat === "") {
                return false;
            }

            $flatNumber = ctype_digit($flat) ? (int)$flat : null;
            $ranges = explode(",", $visibleForFlats);

            foreach ($ranges as $range) {
                $range = trim($range);

                if ($range === "") {
                    continue;
                }

                if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $matches)) {
                    if ($flatNumber === null) {
                        continue;
                    }

                    $from = (int)$matches[1];
                    $to = (int)$matches[2];

                    if ($from > $to) {
                        [ $from, $to ] = [ $to, $from ];
                    }

                    if ($flatNumber >= $from && $flatNumber <= $to) {
                        return true;
                    }

                    continue;
                }

                if ($flat === $range) {
                    return true;
                }

                if ($flatNumber !== null && ctype_digit($range) && $flatNumber === (int)$range) {
                    return true;
                }
            }

            return false;
        }
    }

    if (!function_exists("mobile_camera_path_visible_for_flats")) {
        function mobile_camera_path_visible_for_flats(array $camera, $households): ?string
        {
            static $pathVisibleForFlats = [];

            $path = $camera["path"] ?? null;

            if (!$path) {
                return null;
            }

            if (!array_key_exists($path, $pathVisibleForFlats)) {
                $pathVisibleForFlats[$path] = $households->getPathVisibleForFlats($path);
            }

            $visibleForFlats = $pathVisibleForFlats[$path];

            if ($visibleForFlats === null) {
                return null;
            }

            $visibleForFlats = trim((string)$visibleForFlats);

            return $visibleForFlats === "" ? null : $visibleForFlats;
        }
    }

    if (!function_exists("mobile_filter_available_cameras")) {
        function mobile_filter_available_cameras(array $cameras, array $flatNumbers, $households): array
        {
            $filtered = [];

            foreach ($cameras as $camera) {
                if (!is_array($camera)) {
                    $filtered[] = $camera;
                    continue;
                }

                $visibleForFlats = mobile_camera_path_visible_for_flats($camera, $households);

                if ($visibleForFlats === null) {
                    $filtered[] = $camera;
                    continue;
                }

                foreach ($flatNumbers as $flatNumber) {
                    if (mobile_flat_matches_visible_for_flats($flatNumber, $visibleForFlats)) {
                        $filtered[] = $camera;
                        continue 2;
                    }
                }
            }

            return $filtered;
        }
    }
