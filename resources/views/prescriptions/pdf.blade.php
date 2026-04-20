<!DOCTYPE html>
<html>

<head>
    <title>Prescription</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap");

        @page {
            margin: 5px;
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
            padding: 20px;
            padding-top: 0;
            width: 90%;
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
            width: 50px;
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
                            <img src="{{ public_path('storage/' . $prescription->logo) }}" alt="Hospital Logo"
                                class="hospital-logo" style="width: 50px; height: auto; margin-bottom: 10px" />
                            <h1 style="font-size: 24px; color: #333; margin: 0">
                                {{$prescription->clinic_name}}
                            </h1>
                            <p style="
                    font-size: 12px;
                    color: #666;
                    margin-top: 5px;
                    margin-bottom: 0;
                  ">
                                {{$prescription->address}}<br />
                                Phone: {{$prescription->phone}}
                                ,{{$prescription->phone_second}} <br />
                                Email: {{$prescription->email}}
                            </p>
                        </div>
                    </td>

                    <!-- prescription Title Section -->
                    <td style="
                vertical-align: bottom;
                text-align: right;
                width: 50%;
                border: 0;
                padding: 0;
              ">
                        <h1 style="
                  font-size: 32px;
                  color: #333;
                  margin: 0;
                  padding: 0;
                  text-transform: uppercase;
                ">
                            prescription
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
                            <p>Patient ID: {{ $prescription->patient_id }} MRN: {{ $prescription->mrn??"--" }} </p>
                            <p>
                                Name: {{ $prescription->patient_f_name }} {{
                  $prescription->patient_l_name }}
                            </p>
                            <p>Age:
                                @if ($prescription->patient_dob)
                                {{ \DateTime::createFromFormat('Y-m-d', $prescription->patient_dob)->diff(new \DateTime('now'))->y }} Y
                                @else
                                N/A
                                @endif
                            </p>
                            <p>Gender: {{ $prescription->patient_gender }}</p>
                        </td>
                        <td style="text-align: right">
                            <p>Pulse Rate: {{ $prescription->pulse_rate ?? 0 }}</p>
                            <p>Temperature: {{ $prescription->temperature ??0 }}</p>
                            <p>Blood Pressure: {{ $prescription->blood_pressure??0 }}</p>
                        </td>
                    </tr>
                    <tr></tr>
                </tbody>
            </table>
        </div>
        <!-- Prescription details -->
        <div class="prescription-details">
            <h2 style="text-align: left">Medicines</h2>
            <table style="
            font-size: 14px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
          
          ">
                <thead>
                    <tr>
                        <th style="
                  font-size: 14px;
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 8px;
                  text-align: left;
                  padding-left:0;
                ">
                            Medicine Name
                        </th>
                        <th style="
                  font-size: 14px;
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 8px;
                  text-align: left;
                  padding-left:0;
                ">Days</th>
                        <th style="
                  font-size: 14px;
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 8px;
                  text-align: left;
                  padding-left:0;
                ">
                            Time
                        </th>
                        <th style="
                  font-size: 14px;
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 8px;
                  text-align: left;
                  padding-left:0;
                ">
                            Notes
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescription->items as $item)
                    <tr>
                        <td style="border: none; padding: 8px; font-size: 14px;padding-left:0;">
                            {{ $item->medicine_name }}
                        </td>
                        <td style="border: none; padding: 8px; font-size: 14px;padding-left:0;">
                            {{ $item->duration }}
                        </td>
                        <td style="border: none; padding: 8px; font-size: 14px;padding-left:0;">
                            {{ $item->time }}
                        </td>
                        <td style="border: none; padding: 8px; font-size: 14px;padding-left:0;">
                            {{ $item->notes ?? 'N/A' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Prescribed Tests -->
        @if($prescription->prescribe_test && count($prescription->prescribe_test) > 0)
        <div class="prescribed-tests" style="margin-top:30px">
            <h2 style="text-align: left">Prescribed Tests</h2>
            <table style="
            font-size: 14px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
          ">
                <thead>
                    <tr>
                        <th style="
                  font-size: 14px;
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 8px;
                  text-align: left;
                  padding-left:0;
                ">
                            Test Name
                        </th>
                        <th style="
                  font-size: 14px;
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 8px;
                  text-align: left;
                  padding-left:0;
                ">
                            Notes
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescription->prescribe_test as $test)
                    <tr>
                        <td style="border: none; padding: 8px; font-size: 14px;padding-left:0;">
                            {{ $test->lab_test }}
                        </td>
                        <td style="border: none; padding: 8px; font-size: 14px;padding-left:0;">
                            {{ $test->notes ?? '' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <br />
        <br />
        @if($prescription->problem_desc || $prescription->advice)
        <div class="problems" style="margin-top: 20px;">
            @if($prescription->problem_desc)
            <p><strong>Problem Description:</strong> {{ $prescription->problem_desc }}</p><br>
            @endif

            @if($prescription->advice)
            <p><strong>Advice:</strong> {{ $prescription->advice }}</p>
            @endif
        </div>
        <hr />
        @endif

        {{-- Additional Info --}}
        @php
        $additionalFields = [
        'food_allergies' => 'Food Allergies',
        'tendency_bleed' => 'Tendency to Bleed',
        'heart_disease' => 'Heart Disease',
        'diabetic' => 'Diabetic',
        'surgery' => 'Surgery',
        'accident' => 'Accident',
        'others' => 'Others',
        'medical_history' => 'Medical History',
        'current_medication' => 'Current Medication',
        'female_pregnancy' => 'Female Pregnancy',
        'breast_feeding' => 'Breast Feeding',
        ];
        @endphp

        @if(collect($additionalFields)->some(fn($label, $key) => !empty($prescription->$key)))
        <div class="additional-info">
            <h3>Others -</h3>
            @foreach($additionalFields as $key => $label)
            @if(!empty($prescription->$key))
            <p><strong>{{ $label }}:</strong> {{ $prescription->$key }}</p>
            @endif
            @endforeach
        </div>
        @endif
        <div class="footer">
            <p>Generated on {{ now() }}</p>
        </div>
    </div>
</body>

</html>