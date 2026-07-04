<?php

namespace App\Domains\Projects\Support\ProjectTabs;

use App\Domains\Projects\Contracts\ProjectTabPanelInterface;
use App\Domains\Projects\Models\Project;

class LivewireComponentTabPanel implements ProjectTabPanelInterface
{
    /**
     * @param  array<string, mixed>  $baseProps
     * @param  array<string, string>  $viewStateProps
     * @param  array<string, array{component:string,baseProps?:array<string, mixed>,modeProp?:string|null,detailProp?:string|null,createModeProp?:string|null,keyPattern?:string,appendCreateSuffix?:bool,viewStateProps?:array<string, string>}>  $modeViews
     * @param  array{component:string,baseProps?:array<string, mixed>,modeProp?:string|null,detailProp?:string|null,createModeProp?:string|null,keyPattern?:string,appendCreateSuffix?:bool,viewStateProps?:array<string, string>}|null  $detailView
     */
    public function __construct(
        private readonly string $component,
        private readonly array $baseProps = [],
        private readonly ?string $modeProp = null,
        private readonly ?string $detailProp = null,
        private readonly ?string $createModeProp = null,
        private readonly string $keyPattern = 'project-{tab}-tab-{projectId}',
        private readonly bool $appendCreateSuffix = false,
        private readonly array $viewStateProps = [],
        private readonly array $modeViews = [],
        private readonly ?array $detailView = null,
    ) {}

    /**
     * @param  array<string, array{modeParam:string,mode:string,detailParam:?string,detailId:string,isCreateMode:bool}>  $tabContext
     * @param  array<string, mixed>  $viewState
     * @return array{component:string,props:array<string, mixed>,key:string}
     */
    public function resolve(string $tabKey, Project $project, array $tabContext, array $viewState = []): ?array
    {
        $context = $tabContext[$tabKey] ?? [
            'mode' => '',
            'detailId' => '',
            'isCreateMode' => false,
        ];

        $resolvedView = $this->resolveView($context);

        $component = $resolvedView['component'];
        $baseProps = $resolvedView['baseProps'] ?? $this->baseProps;
        $modeProp = array_key_exists('modeProp', $resolvedView) ? $resolvedView['modeProp'] : $this->modeProp;
        $detailProp = array_key_exists('detailProp', $resolvedView) ? $resolvedView['detailProp'] : $this->detailProp;
        $createModeProp = array_key_exists('createModeProp', $resolvedView) ? $resolvedView['createModeProp'] : $this->createModeProp;
        $keyPattern = $resolvedView['keyPattern'] ?? $this->keyPattern;
        $appendCreateSuffix = $resolvedView['appendCreateSuffix'] ?? $this->appendCreateSuffix;
        $viewStateProps = $resolvedView['viewStateProps'] ?? $this->viewStateProps;

        $props = array_merge(['project' => $project], $baseProps);

        if ($modeProp !== null) {
            $props[$modeProp] = (string) ($context['mode'] ?? '');
        }

        if ($detailProp !== null) {
            $props[$detailProp] = (string) ($context['detailId'] ?? '');
        }

        if ($createModeProp !== null) {
            $props[$createModeProp] = (bool) ($context['isCreateMode'] ?? false);
        }

        foreach ($viewStateProps as $propName => $viewStateKey) {
            $value = $viewState[$viewStateKey] ?? null;

            if (is_array($value)) {
                $value = $value[$tabKey] ?? null;
            }

            $props[$propName] = $value;
        }

        $keyReplacements = [
            '{tab}' => $tabKey,
            '{projectId}' => (string) $project->id,
            '{detailId}' => (string) ($context['detailId'] ?? ''),
            '{mode}' => (string) ($context['mode'] ?? ''),
        ];

        foreach ($viewState as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $keyReplacements['{'.$key.'}'] = (string) $value;
            }
        }

        $resolvedKey = strtr($keyPattern, $keyReplacements);

        if ($appendCreateSuffix && (bool) ($context['isCreateMode'] ?? false)) {
            $resolvedKey .= '-create';
        }

        return [
            'component' => $component,
            'props' => $props,
            'key' => $resolvedKey,
        ];
    }

    /**
     * @param  array{mode?:mixed,detailId?:mixed,isCreateMode?:mixed}  $context
     * @return array{component:string,baseProps?:array<string, mixed>,modeProp?:string|null,detailProp?:string|null,createModeProp?:string|null,keyPattern?:string,appendCreateSuffix?:bool,viewStateProps?:array<string, string>}
     */
    private function resolveView(array $context): array
    {
        $detailId = (string) ($context['detailId'] ?? '');

        if ($detailId !== '' && is_array($this->detailView)) {
            return $this->detailView;
        }

        $mode = (string) ($context['mode'] ?? '');

        if ($mode !== '' && array_key_exists($mode, $this->modeViews)) {
            return $this->modeViews[$mode];
        }

        return [
            'component' => $this->component,
            'baseProps' => $this->baseProps,
            'modeProp' => $this->modeProp,
            'detailProp' => $this->detailProp,
            'createModeProp' => $this->createModeProp,
            'keyPattern' => $this->keyPattern,
            'appendCreateSuffix' => $this->appendCreateSuffix,
            'viewStateProps' => $this->viewStateProps,
        ];
    }
}
