<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Video Scripting Skill (Layer 5 — Platform Intelligence)
 *
 * Equips agents to plan, script, and structure video content programmatically.
 * Inspired by remotion-dev/remotion — brings the concept of "code-driven video"
 * into the agent layer: agents can produce structured scene definitions,
 * scripts, and render configs that a video pipeline can consume directly.
 *
 * Actions:
 *   script          – produce a timed video script from a brief
 *   storyboard      – generate a visual scene-by-scene storyboard
 *   scene_breakdown – split an existing script into discrete scenes
 *   render_config   – produce a Remotion-compatible composition config
 */
class VideoScriptingSkill extends BaseSkill
{
    /** Default video duration targets in seconds by format. */
    private const FORMAT_DURATIONS = [
        'short' => 60,
        'standard' => 180,
        'long' => 600,
        'reel' => 30,
        'explainer' => 120,
    ];

    public function key(): string
    {
        return 'video-scripting';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Input keys:
     *   action      – script | storyboard | scene_breakdown | render_config
     *   topic       – video topic / subject
     *   brief       – extended brief string
     *   format      – short | standard | long | reel | explainer (default: standard)
     *   audience    – target audience description
     *   tone        – professional | casual | educational | inspirational
     *   scenes      – array of scene objects (for scene_breakdown / render_config)
     *   fps         – frames per second for render_config (default: 30)
     *   width       – canvas width px (default: 1920)
     *   height      – canvas height px (default: 1080)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'script';

        return match ($action) {
            'script' => $this->generateScript($input),
            'storyboard' => $this->generateStoryboard($input),
            'scene_breakdown' => $this->breakdownScenes($input),
            'render_config' => $this->buildRenderConfig($input),
            default => SkillResult::failed("Unknown video-scripting action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

    private function generateScript(array $input): SkillResult
    {
        $topic = $input['topic'] ?? '';
        $brief = $input['brief'] ?? $topic;
        $format = $input['format'] ?? 'standard';
        $tone = $input['tone'] ?? 'professional';
        $audience = $input['audience'] ?? 'general';

        if (empty($topic)) {
            return SkillResult::failed('A topic is required to generate a video script');
        }

        $totalDuration = self::FORMAT_DURATIONS[$format] ?? self::FORMAT_DURATIONS['standard'];
        $structure = $this->getScriptStructure($format, $tone);

        $sections = [];
        $timeOffset = 0;

        foreach ($structure as $section) {
            $duration = (int) round($totalDuration * $section['weight']);
            $sections[] = [
                'section' => $section['name'],
                'start_sec' => $timeOffset,
                'end_sec' => $timeOffset + $duration,
                'duration_sec' => $duration,
                'narration_cue' => $section['cue'],
                'suggested_visual' => $section['visual'],
                'tone_note' => $section['tone_note'] ?? $tone,
                'script_placeholder' => "[Write {$duration}s of {$section['name']} content about: {$brief}]",
            ];
            $timeOffset += $duration;
        }

        return SkillResult::completed(
            [
                'topic' => $topic,
                'format' => $format,
                'total_duration_sec' => $totalDuration,
                'target_audience' => $audience,
                'tone' => $tone,
                'section_count' => count($sections),
                'sections' => $sections,
                'estimated_word_count' => (int) round($totalDuration * 2.5), // ~150 wpm
            ],
            85.0,
            [],
            ['Replace [script_placeholder] text with actual narration before production']
        );
    }

    private function generateStoryboard(array $input): SkillResult
    {
        $topic = $input['topic'] ?? '';
        $brief = $input['brief'] ?? $topic;
        $format = $input['format'] ?? 'standard';
        $sceneCount = (int) ($input['scene_count'] ?? 6);

        if (empty($topic)) {
            return SkillResult::failed('A topic is required to generate a storyboard');
        }

        $totalDuration = self::FORMAT_DURATIONS[$format] ?? self::FORMAT_DURATIONS['standard'];
        $sceneDuration = (int) round($totalDuration / $sceneCount);

        $frames = [];
        for ($i = 1; $i <= $sceneCount; $i++) {
            $frames[] = [
                'frame' => $i,
                'start_sec' => ($i - 1) * $sceneDuration,
                'end_sec' => $i * $sceneDuration,
                'duration_sec' => $sceneDuration,
                'visual_description' => $this->storyboardVisual($i, $sceneCount, $brief),
                'camera_angle' => $this->cameraAngle($i, $sceneCount),
                'text_overlay' => $i === 1 ? $topic : ($i === $sceneCount ? 'CTA + Logo' : null),
                'audio_cue' => $i === 1 ? 'Intro music fade in' : ($i === $sceneCount ? 'Outro music fade out' : 'Ambient / VO continues'),
                'transition' => $i < $sceneCount ? 'cut' : 'fade_out',
            ];
        }

        return SkillResult::completed(
            [
                'topic' => $topic,
                'format' => $format,
                'total_duration_sec' => $totalDuration,
                'frame_count' => $sceneCount,
                'frames' => $frames,
            ],
            82.0,
            [],
            ['Attach this storyboard to your creative brief for the video production team']
        );
    }

    private function breakdownScenes(array $input): SkillResult
    {
        $script = $input['script'] ?? $input['content'] ?? '';
        $scenes = $input['scenes'] ?? [];

        if (empty($script) && empty($scenes)) {
            return SkillResult::failed('Either a script or existing scenes are required for scene_breakdown');
        }

        if (! empty($scenes)) {
            // Enhance existing scenes with timing estimates
            $enhanced = array_map(function (array $scene, int $idx) {
                $words = str_word_count($scene['narration'] ?? $scene['text'] ?? '');
                $estimatedDuration = max(3, (int) round($words / 2.5)); // 150 wpm

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

        // Break raw script by sentence / paragraph
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

        // Convert each scene to a Remotion-compatible sequence definition
        $sequences = array_map(function (array $scene, int $idx) use ($fps) {
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
                'generated_by' => 'dot-agents/video-scripting-skill',
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

    // ── Helpers ──────────────────────────────────────────

    private function getScriptStructure(string $format, string $tone): array
    {
        $structures = [
            'reel' => [
                ['name' => 'Hook', 'weight' => 0.20, 'cue' => 'Grab attention instantly', 'visual' => 'Bold text / striking visual'],
                ['name' => 'Core Message', 'weight' => 0.50, 'cue' => 'Deliver value fast', 'visual' => 'Demo / B-roll'],
                ['name' => 'CTA', 'weight' => 0.30, 'cue' => 'Drive one clear action', 'visual' => 'Logo + link'],
            ],
            'explainer' => [
                ['name' => 'Hook', 'weight' => 0.10, 'cue' => 'State the problem', 'visual' => 'Pain-point illustration'],
                ['name' => 'Problem Agitation', 'weight' => 0.20, 'cue' => 'Amplify the pain', 'visual' => 'Problem scenario'],
                ['name' => 'Solution', 'weight' => 0.40, 'cue' => 'Introduce the solution', 'visual' => 'Product / process demo'],
                ['name' => 'Social Proof', 'weight' => 0.15, 'cue' => 'Build credibility', 'visual' => 'Testimonials / stats'],
                ['name' => 'CTA', 'weight' => 0.15, 'cue' => 'Drive next action', 'visual' => 'Logo + URL'],
            ],
            'standard' => [
                ['name' => 'Intro', 'weight' => 0.10, 'cue' => 'Brand intro and context', 'visual' => 'Logo animation'],
                ['name' => 'Hook', 'weight' => 0.10, 'cue' => 'State the key promise', 'visual' => 'Key stat or headline'],
                ['name' => 'Main Content', 'weight' => 0.55, 'cue' => 'Deliver core value', 'visual' => 'Primary footage / demo'],
                ['name' => 'Summary', 'weight' => 0.10, 'cue' => 'Recap key points', 'visual' => 'Key takeaways card'],
                ['name' => 'CTA', 'weight' => 0.15, 'cue' => 'Clear next step', 'visual' => 'Contact / URL overlay'],
            ],
        ];

        return $structures[$format] ?? $structures['standard'];
    }

    private function storyboardVisual(int $frameNum, int $total, string $topic): string
    {
        $position = $frameNum / $total;

        if ($position <= 0.15) {
            return "Opening shot: Brand logo / title card with \"{$topic}\" text overlay";
        }
        if ($position <= 0.3) {
            return "Wide establishing shot introducing the context of {$topic}";
        }
        if ($position <= 0.7) {
            return "Medium shot: core demonstration / key information about {$topic}";
        }
        if ($position <= 0.85) {
            return 'Close-up or data graphic reinforcing the key message';
        }

        return 'Closing shot: Logo, tagline, and call-to-action overlay';
    }

    private function cameraAngle(int $frameNum, int $total): string
    {
        $angles = ['wide', 'medium', 'close_up', 'over_the_shoulder', 'aerial', 'medium'];

        return $angles[($frameNum - 1) % count($angles)];
    }
}
