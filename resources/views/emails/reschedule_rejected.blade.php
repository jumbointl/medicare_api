<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Rejected</title>
</head>
<body>
    <h2>Hello, {{ $recipientName }}</h2>
    <p>Your reschedule request has been <strong>rejected</strong>.</p>
    <p><strong>Original appointment:</strong> {{ $originalDate }} at {{ $originalTime }}</p>
    <p><strong>You had requested:</strong> {{ $requestedDate }} at {{ $requestedTime }}</p>
    @if(!empty($notes))
        <p><strong>Reason:</strong> {{ $notes }}</p>
    @endif
    <p>The original appointment remains as scheduled. Please contact the clinic if you need further assistance.</p>
    <br>
    <p>Best regards, Medicare</p>
</body>
</html>
