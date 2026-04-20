<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to eSewa...</title>
</head>
<body onload="document.forms['esewaForm'].submit();">

<form name="esewaForm" method="POST" action="https://uat.esewa.com.np/epay/main">
    {{-- Use https://esewa.com.np/epay/main for LIVE --}}

    <input type="hidden" name="amt" value="{{ $amount }}">
    <input type="hidden" name="pdc" value="0">
    <input type="hidden" name="psc" value="0">
    <input type="hidden" name="txAmt" value="0">
    <input type="hidden" name="tAmt" value="{{ $amount }}">
    <input type="hidden" name="pid" value="{{ $transaction_id }}">
    <input type="hidden" name="scd" value="{{ $merchant_code }}">
    <input type="hidden" name="su" value="{{ $success_url }}">
    <input type="hidden" name="fu" value="{{ $failed_url }}">

</form>

<p>Redirecting to eSewa…</p>
</body>
</html>
