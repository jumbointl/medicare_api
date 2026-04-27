<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Request</title>
</head>
<body>
    <h2>Hello, {{ $recipientName }}</h2>
    <p>{{ $intro }}</p>
    <p><strong>Original:</strong> {{ $originalDate }} at {{ $originalTime }}</p>
    <p><strong>Requested:</strong> {{ $requestedDate }} at {{ $requestedTime }}</p>
    @if(!empty($notes))
        <p><strong>Notes:</strong> {{ $notes }}</p>
    @endif
    <p>{{ $closing }}</p>
    <br>
    <p>Best regards, Medicare</p>
</body>
</html>
