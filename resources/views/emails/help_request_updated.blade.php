<p>Hi {{ $helpRequest->name }},</p>
<p>Your help request has been updated.</p>
<ul>
    <li><strong>Status:</strong> {{ $helpRequest->status }}</li>
    @if ($progressMessage)
        <li><strong>Progress:</strong> {{ $progressMessage }}</li>
    @endif
</ul>