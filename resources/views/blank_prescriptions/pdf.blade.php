<!DOCTYPE html>
<html>

<head>
    <title>Prescription</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap");

        @page {
            margin: 2px;
        }

        body {
            font-family: "Inter", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        hr {
            border-color: #000;
            background-color: #000;
        }

        .container {

            padding-top: 0;
            width: 100%;
            margin: auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 0px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header .headerMain {
            display: flex;
            align-items: start;
            justify-content: space-between;
        }

        .header .hospital-name {
            font-size: 24px;
            color: #333;
            font-weight: bold;
        }

        .header .hospital-logo {
            width: 70px;
            height: auto;
            margin-bottom: 10px;
        }

        .hospital-contact {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .prescription-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        .patient-details p {
            margin: 0;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .problems p {
            margin: 0;
            margin-bottom: 5px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            padding: 0;
        }

        .total {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            padding: 10px;
            border-top: 1px solid #000;
        }

        .footer {
            text-align: left;
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }

        .footer p {
            margin: 0;
            margin-bottom: 6px;
            margin-top: 20px;
            text-align: center
        }

        .additional-info {
            font-size: 12px;
            color: #666;
            margin-top: 50px;
        }

        .additional-info p {
            margin: 5px;
        }

        .paymentStatus {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <table style="width: 100%; border: 0">
                <tr>
                    <!-- Hospital Logo and Name Section -->
                    <td style="vertical-align: top; width: 50%; border: 0; padding: 0">
                        <div style="text-align: left">
                           
                           @if (!empty($prescription->logo))
    <img src="{{ public_path('storage/' . $prescription->logo) }}" alt="Hospital Logo"
        class="hospital-logo" style="width: 70px; height: auto; margin-bottom: 10px" />
@endif
                            <h1 style="font-size: 24px; color: #333; margin: 0">
                                {{ $prescription->clinic_name }}
                            </h1>
                            <p
                                style="
                    font-size: 12px;
                    color: #666;
                    margin-top: 5px;
                    margin-bottom: 0;
                  ">
                                {{ $prescription->address }}<br />
                                Phone: {{ $prescription->phone }}
                                ,{{ $prescription->phone_second }} <br />
                                Email: {{ $prescription->email }}
                            </p>
                        </div>
                    </td>

                    <!-- prescription Title Section -->
                    <td
                        style="
                vertical-align: bottom;
                text-align: right;
                width: 50%;
                border: 0;
                padding: 0;
              ">
                        <h1
                            style="
                  font-size: 18px;
                  color: #333;
                  margin: 0;
                  padding: 0;
                  text-transform: uppercase;
                ">
                            Consultation Report
                        </h1>
                    </td>
                </tr>
            </table>
            <hr />
        </div>
        <!-- Patient details -->
        <div class="patient-details">
            <table>
                <tbody>
                    <tr>
                        <td>
                            <p>Patient ID: {{ $prescription->patient_id }}</p>
                            <p>
                                Name: {{ $prescription->patient_f_name }} {{ $prescription->patient_l_name }}
                            </p>
                            <p>Age: @if ($prescription->patient_dob)
                                    {{ \DateTime::createFromFormat('Y-m-d', $prescription->patient_dob)->diff(new \DateTime('now'))->y }}
                                    Y
                                @else
                                @endif
                            </p>
                            <p>Gender: {{ $prescription->patient_gender }}</p>
                            <p>Phone: {{ $prescription->patient_phone }}</p>
                        </td>
                        <td style="text-align: right">
                            <p>Dr. {{ $prescription->doct_f_name }} {{ $prescription->doct_l_name }}</p>
                            <p>{{ $prescription->doct_specialization }}</p>
                            <p>{{ $prescription->dept_title }}</p>
                        </td>
                    </tr>
                    <tr></tr>
                </tbody>
            </table>
        </div>
        <hr />


    </div>
</body>

</html>
