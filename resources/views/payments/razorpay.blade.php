<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
new Razorpay({
  key: "{{ $key }}",
  order_id: "{{ $orderId }}",
  amount: "{{ $amount * 100 }}",
  currency: "INR",
handler: function (res) {
    window.location.href =
      "{{ route('payment.razorpay.success') }}" +
      "?pre_order_id={{ $preOrderId }}" +
      "&razorpay_payment_id=" + res.razorpay_payment_id +
      "&razorpay_order_id=" + res.razorpay_order_id +
      "&razorpay_signature=" + res.razorpay_signature;
  },

  modal: {
    ondismiss: function () {
      window.location.href =
        "{{ route('payment.razorpay.failed') }}" +
        "?pre_order_id={{ $preOrderId }}";
    }
  }
}).open();
</script>
