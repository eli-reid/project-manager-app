<?php

namespace App\Domains\Projects\Support\ProjectTabs;

use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;

class LivewireComponentTabPanel implements ProjectTabPanel
{
    /**
     * @param  array<string, mixed>  $baseProps
     */
    public function __construct(
        private readonly string $component,
        private readonly array $baseProps = [],
        private readonly ?string $modeProp = null,
        private readonly ?string $detailProp = null,
        private readonly ?string $createModeProp = null,
        private readonly string $keyPattern = 'project-{tab}-tab-{projectId}',
        private readonly bool $appendCreateSuffix = false,
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

        $props = array_merge(['project' => $project], $this->baseProps);

        if ($this->modeProp !== null) {
            $props[$this->modeProp] = (string) ($context['mode'] ?? '');
        }

        if ($this->detailProp !== null) {
            $props[$this->detailProp] = (string) ($context['detailId'] ?? '');
        }

        if ($this->createModeProp !== null) {
            $props[$this->createModeProp] = (bool) ($context['isCreateMode'] ?? false);
        }

        $keyReplacements = [
            '{tab}' => $tabKey,
            '{projectId}' => (string) $project->id,
        ];

        foreach ($viewState as $stateKey => $stateValue) {
            if (! is_scalar($stateValue)) {
                continue;
            }

            $keyReplacements['{'.$stateKey.'}'] = (string) $stateValue;
        }

        $key = strtr($this->keyPattern, $keyReplacements);

        if ($this->appendCreateSuffix && ($context['isCreateMode'] ?? false)) {
            $key .= '-create';
        }

        return [
            'component' => $this->component,
            'props' => $props,
            'key' => $key,
        ];
    }
}
