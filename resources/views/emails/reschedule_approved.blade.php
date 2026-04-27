<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Approved</title>
</head>
<body>
    <h2>Hello, {{ $recipientName }}</h2>
    <p>Your reschedule request has been <strong>approved</strong>.</p>
    <p><strong>Previous:</strong> {{ $originalDate }} at {{ $originalTime }}</p>
    <p><strong>New:</strong> {{ $newDate }} at {{ $newTime }}</p>
    <p>Doctor: Dr. {{ $doctorName }}</p>
    <br>
    <p>Best regards, Medicare</p>
</body>
</html>
