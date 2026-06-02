<div>
    @if ($coverMessage)
        <p>{!! nl2br(e($coverMessage)) !!}</p>
        <br>
    @endif

    <pre style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; white-space: pre-wrap; line-height: 1.45;">{{ $formalBody }}</pre>
</div>
