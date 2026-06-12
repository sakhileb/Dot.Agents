<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * VideoProductionSkill — scene breakdown and render config (Layer 5 — Platform Intelligence)
 *
 * Handles the two production-focused video actions extracted from VideoScriptingSkill:
 *   scene_breakdown – split a script into discrete timed scenes
 *   render_config   – produce a Remotion-compatible composition config
 *
 * Shares camera/visual helpers with VideoScriptWriterSkill via VideoScriptHelper.
 */
class VideoProductionSkill extends VideoScriptHelper
{
    public function key(): string
    {
        return 'video-production';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'scene_breakdown';

        return match ($action) {
            'scene_breakdown' => $this->breakdownScenes($input),
            'render_config' => $this->buildRenderConfig($input),
            default => SkillResult::failed("Unknown video-production action: [{$action}]"),
        };
    }

    private function breakdownScenes(array $input): SkillResult
    {
        $script = $input['script'] ?? $input['content'] ?? '';
        $scenes = $input['scenes'] ?? [];

        if (empty($script) && empty($scenes)) {
            return SkillResult::failed('Either a script or existing scenes are required for scene_breakdown');
        }

        if (! empty($scenes)) {
            $enhanced = array_map(function (array $scene, int $idx) {
                $words = str_word_count($scene['narration'] ?? $scene['text'] ?? '');
                $estimatedDuration = max(3, (int) round($words / 2.5));

                return array_merge($scene, [
                    'scene_number' => $idx + 1,
                    'estimated_duration_sec' => $estimatedDuration,
                    'word_count' => $words,
                ]);
            }, $scenes, array_keys($scenes));

            $totalDuration = array_sum(array_column($enhanced, 'estimated_duration_sec'));

            return SkillResult::completed(
                [
                    'scene_count' => count($enhanced),
                    'total_estimated_duration_sec' => $totalDuration,
                    'scenes' => $enhanced,
                ],
                90.0
            );
        }

        $paragraphs = array_values(array_filter(explode("\n\n", $script)));
        $parsedScenes = array_map(function (string $para, int $idx) {
            $words = str_word_count($para);
            $duration = max(3, (int) round($words / 2.5));

            return [
                'scene_number' => $idx + 1,
                'narration' => trim($para),
                'word_count' => $words,
                'estimated_duration_sec' => $duration,
            ];
        }, $paragraphs, array_keys($paragraphs));

        $totalDuration = array_sum(array_column($parsedScenes, 'estimated_duration_sec'));

        return SkillResult::completed(
            [
                'scene_count' => count($parsedScenes),
                'total_estimated_duration_sec' => $totalDuration,
                'scenes' => $parsedScenes,
            ],
            88.0
        );
    }

    private function buildRenderConfig(array $input): SkillResult
    {
        $scenes = $input['scenes'] ?? [];
        $fps = (int) ($input['fps'] ?? 30);
        $width = (int) ($input['width'] ?? 1920);
        $height = (int) ($input['height'] ?? 1080);
        $compositionId = $input['composition_id'] ?? 'DotAgentsVideo';

        if (empty($scenes)) {
            return SkillResult::failed('Scenes array is required to build a render config');
        }

        $sequences = array_map(function (array $scene, int $idx) use ($fps, $scenes) {
            $durationFrames = ($scene['duration_sec'] ?? $scene['estimated_duration_sec'] ?? 3) * $fps;
            $fromFrame = array_sum(array_map(
                fn ($i) => ($scenes[$i]['duration_sec'] ?? $scenes[$i]['estimated_duration_sec'] ?? 3) * $fps,
                range(0, $idx - 1)
            ));

            return [
                'id' => "scene_{$scene['scene_number']}",
                'component' => 'SceneComponent',
                'from' => (int) $fromFrame,
                'durationInFrames' => (int) $durationFrames,
                'props' => [
                    'narration' => $scene['narration'] ?? $scene['text'] ?? '',
                    'visual_description' => $scene['visual_description'] ?? null,
                    'text_overlay' => $scene['text_overlay'] ?? null,
                    'camera_angle' => $scene['camera_angle'] ?? 'medium',
                    'transition' => $scene['transition'] ?? 'cut',
                ],
            ];
        }, $scenes, array_keys($scenes));

        $totalFrames = array_sum(array_column($sequences, 'durationInFrames'));

        $config = [
            'composition' => [
                'id' => $compositionId,
                'fps' => $fps,
                'width' => $width,
                'height' => $height,
                'durationInFrames' => $totalFrames,
                'defaultProps' => [],
            ],
            'sequences' => $sequences,
            'output' => [
                'codec' => 'h264',
                'format' => 'mp4',
                'estimated_duration_sec' => round($totalFrames / $fps, 1),
            ],
            'meta' => [
                'generated_by' => 'dot-agents/video-production-skill',
                'generated_at' => now()->toIso8601String(),
                'scene_count' => count($sequences),
            ],
        ];

        return SkillResult::completed(
            [
                'render_config' => $config,
                'total_frames' => $totalFrames,
                'estimated_duration_sec' => round($totalFrames / $fps, 1),
                'fps' => $fps,
                'resolution' => "{$width}x{$height}",
            ],
            90.0,
            [],
            ['Pass render_config to your Remotion renderMedia() call or a video rendering queue job']
        );
    }
}
