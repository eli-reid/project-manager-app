@props(['sections' => []])

<nav aria-label="Main navigation">
    <ul class="nav-sections">
        @foreach($sections as $section)
            <li class="nav-section" data-key="{{ $section['key'] }}">
                <div class="nav-section-label">{{ $section['label'] }}</div>

                @if(!empty($section['groups']))
                    <ul class="nav-groups">
                        @foreach($section['groups'] as $group)
                            <li class="nav-group" data-key="{{ $group['id'] }}">
                                <div class="nav-group-label">{{ $group['label'] }}</div>
                                <ul class="nav-items">
                                    @foreach($group['items'] as $item)
                                        <li class="nav-item">
                                            <a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if(!empty($section['items']))
                    <ul class="nav-items--section">
                        @foreach($section['items'] as $item)
                            <li class="nav-item">
                                <a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
