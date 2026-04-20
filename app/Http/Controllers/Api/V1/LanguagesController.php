<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LanguagesModel;
use App\Models\LanguagesFileModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class LanguagesController extends Controller
{

private $web_default_lng_json = [
    "featured_post" => "Featured Post",
    "about us" => "About Us",
    "legal" => "Legal",
    "terms and conditions" => "Terms and Conditions",
    "privacy policy" => "Privacy Policy",
    "make-appointment" => "Make Appointment",
    "departments" => "Departments",
    "department" => "Department",
    "clinics" => "Clinics",
    "clinic" => "Clinic",
    "labs" => "Labs",
    "lab" => "Lab",
    "doctors" => "Doctors",
    "Doctor" => "Doctor",
    "patient" => "Patient",
    "appointment" => "Appointment",
    "appointments" => "Appointments",
    "prescriptions" => "Prescriptions",
    "prescription" => "Prescription",
    "prescription-details" => "Prescription Details",
    "files" => "Files",
    "file" => "File",
    "family-members" => "Family Members",
    "family-member" => "Family Member",
    "vitals" => "Vitals",
    "vital" => "Vital",
    "profile" => "Profile",
    "profile-details" => "Profile Details",
    "see-all" => "See All",
    "testi" => "Testimonials",
    "contact-us" => "Contact Us",
    "date" => "Date",
    "time" => "Time",
    "phone" => "Phone",
    "whatsapp" => "Whatspp",           // ← note: probably typo in original (WhatsApp?)
    "gmail" => "Gmail",
    "location" => "Location",
    "ambulance" => "Ambulance",
    "no" => "No",
    "yes" => "Yes",
    "close" => "Close",
    "add" => "Add",
    "cancel" => "Cancel",
    "dateTime" => "Date & Time",
    "patientDetails" => "Patient Details",
    "Summary" => "Summary",
    "or" => "Or",
    "tax" => "Tax",
    "total" => "Total",
    "book-appointment" => "Book Appointment",
    "search" => "Search",
    "swipe" => "Swipe",
    "sorry" => "Sorry",
    "notAvailable" => "{{name}} are not available in your selected location.",
    "and" => "and",
    "loading" => "Loading",
    "loading.pleaseWait" => "Please wait",
    "select_location" => "Select Location",
    "failed_to_get_city" => "Failed to get city",
    "select_city_manually" => "Please select a city manually",
    "search_for_location" => "Search for a location",
    "use_current_location" => "Use Current Location",
    "category" => "Category",
    "amount" => "Amount",
    "cartDetails" => "Cart Details",

    "chat_popup" => [
        "greeting" => "Hi!, how are you? I'm your AI Health Assistant. How can I help you today?",
        "description" => "Describe your health concern. our AI will recommend the best available doctor in {{city}}",
        "noDoctorsMessage" => "Sorry , no doctors were found matching your search. Please try adjusting your filters or choose a different department.",
        "ask" => "Ask {{name}} anything",
        "needAssistance" => "Need Assistance?",
        "poweredBy" => "Powered by {{name}} AI"
    ],

    "aiBanner" => [
        "header" => "AI Health Assistant",
        "title" => "Have a medical query?",
        "title2" => "Talk with our AI.",
        "description" => "Get the best doctor suggestions in {{city}}."
    ],
    "technicalError" => [
        "title" => "Technical Issue",
        "oops" => "Oops! Something went wrong",
        "description" => "We're currently experiencing some technical difficulties on our end.",
        "tryAgain" => "Please try again after some time or contact our support team.",
        "refresh" => "Try Again",
        "goHome" => "Go to Homepage",
        "needHelp" => "Need help?",
        "contactSupport" => "Contact Support",
        "footer" => "We apologize for the inconvenience",
        "errorCode" => "Error 500",
        "illustrationAlt" => "Error Illustration"
    ],

    "languageSelector" => [
        "selectLanguage" => "Select Language",
        "reloadHint" => "Page will reload to apply changes"
    ],

    "navbar" => [
        "Home" => "Home",
        "Blogs" => "Blogs",
        "Profile" => "Profile",
        "Family-Members" => "Family Members",
        "Appointments" => "Appointments",
        "Doctors" => "Doctors",
        "Vitals" => "Vitals",
        "About-us" => "About-Us",
        "Contact-us" => "Contact-Us",
        "Clinics" => "Clinics",
        "Labs" => "Labs",
        "Sign-up" => "Sign up",
        "Sign-in" => "Sign In",
        "Logout" => "Logout",
        "Member-Since" => "Member Since",
        "Prescriptions" => "Prescription",    // ← intentional singular in original
        "Files" => "Files",
        "About-Us" => "About Us",
        "Contact-Us" => "Contact Us",
        "Wallet" => "Wallet",
        "Lab-Tests-Bookings" => "Lab Tests Bookings",
        "Lab-Test-Bookings" => "Lab Test Bookings"
    ],

    "hero" => [
        "title" => "Welcome to {{appName}}",
        "heading" => "We Are Providing Best & Affordable Health Care.",
        "description" => "Experience Unmatched Healthcare Excellence at {{appName}}: Comprehensive Medical Services, Advanced Hospital Management, and Compassionate Patient Care for a Healthier Tomorrow",
        "button1" => "Get Started",
        "button2" => "Get Appointments"
    ],

    "cta" => [
        "title1" => "Reach Out to Us",
        "description1" => "Feel free to get in touch anytime. We`re ready to assist you!",
        "title2" => "24-Hour Service",
        "description2" => "We take pride in offering 24-hour medical services to ensure you receive the care you need, whenever you need it.",
        "title3" => "Advanced Medical Technology",
        "description3" => "We utilize cutting-edge medical technology to deliver the highest quality care.",
        "button" => "Make Appointment"
    ],

    "weAre" => [
        "title1" => "We are always ensure best",
        "highlight" => "Medical treatment",
        "title2" => "for you",
        "description" => "At our healthcare facility, we are committed to providing top-notch medical care tailored to your needs. With a team of skilled professionals and cutting-edge technology, we ensure you receive the best treatment and compassionate support every step of the way.",
        "benefits" => [
            "Top-tier Specialists in Healthcare.",
            "Advanced Doctor Services Available.",
            "Discounts Offered on All Medical Treatments.",
            "Easy and Swift Enrollment Process."
        ]
    ],

    "clinic-component" => [
        "title" => "Our Clinics",
        "title1" => "Top Clinics in {{city}}",
        "desc" => "Discover specialized healthcare services tailored to your needs, all in one place",
        "see-all" => "See All Clinics"
    ],

    "dept_component" => [
        "title" => "Departments",
        "desc" => "Experience the ease of finding everything you need under one roof with our comprehensive departmental offerings."
    ],

    "doctor_component" => [
        "title" => "Top Doctors",
        "title1" => "Top Doctors in {{city}}",
        "desc" => "Discover the best doctors in your area, ready to provide you with exceptional care and expertise.",
        "experience" => "Exp {{count}}+ Years"
    ],

    "whyUs" => [
        "title" => "Why Choose Us?",
        "desc" => "We are committed to providing you with the best healthcare services, ensuring your well-being and satisfaction.",
        "benefits" => [
            [
                "title" => "Personalized Care",
                "desc" => "At {{appName}}, we prioritize your health and well-being above all else. Our hospital offers comprehensive medical services tailored to meet your individual needs, ensuring you receive the highest quality of care at every step of your journey"
            ],
            [
                "title" => "Expert Team",
                "desc" => "With a dedicated team of experienced healthcare professionals, including doctors, nurses, and support staff, we are committed to providing you with personalized attention and expert medical guidance. You can trust our skilled team to deliver compassionate care and support throughout your treatment"
            ],
            [
                "title" => "Cutting-Edge Facilities",
                "desc" => "Our state-of-the-art facilities are equipped with the latest medical technology, enabling us to deliver advanced diagnostics, treatments, and care in a comfortable and supportive environment"
            ]
        ]
    ],

    "opr_method" => [
        "title" => "Our Operational Method",
        "title2" => "Our Operational Method",
        "desc" => "We serve as your reliable one-stop destination for all your healthcare needs. Our extensive directory is crafted to offer convenient access to a diverse array of healthcare services and providers, guaranteeing optimal care for you and your family.",
        "steps" => [
            "Book An Appointment",
            "Conduct Checkups",
            "Perform Treatment",
            "Prescribe & Payment"
        ]
    ],

    "faqs" => [
        "title" => "Frequently Asked Questions",
        "1" => [
            [
                "question" => "What services does {{appName}} offer?",
                "answer" => "{{appName}} offers a comprehensive range of medical services, including dental, gynecology, orthology, neurology, general medicine, dermatology, and cardiology. We also provide advanced lab testing and diagnostic services."
            ],
            [
                "question" => "What makes {{appName}} different from other healthcare providers?",
                "answer" => "{{appName}} stands out due to its commitment to affordable healthcare, advanced medical technology, top-tier specialists, and 24-hour service. We also offer discounts on all medical treatments and ensure a swift enrollment process."
            ],
            [
                "question" => "How can I book an appointment at {{appName}}?",
                "answer" => "You can easily book an appointment through our website by navigating to the 'Book An Appointment' section. Simply select the service you need, choose a convenient time, and confirm your booking."
            ],
            [
                "question" => "What types of diagnostic tests are available at your lab?",
                "answer" => "Our laboratory offers a wide range of diagnostic tests, including Complete Blood Count (CBC), Hemoglobin (Hb) tests, X-rays, and CT scans. We provide timely and accurate results to support your healthcare needs."
            ],
            [
                "question" => "Are there any discounts available on medical treatments?",
                "answer" => "Yes, {{appName}} offers discounts on all medical treatments. For example, we provide a 5% discount on CBC and Hemoglobin tests, and a 10% discount on X-rays and CT scans."
            ]
        ],
        "2" => [
            [
                "question" => "What are your operating hours?",
                "answer" => "{{appName}} operates 24 hours a day, 7 days a week, ensuring that you receive the care you need whenever you need it."
            ],
            [
                "question" => "Who are the doctors at {{appName}}?",
                "answer" => "Our team consists of highly qualified and experienced doctors specializing in various fields such as cardiology, neurology, dermatology, and more. Detailed information about our doctors is available on the 'Meet Our Doctors' page on our website."
            ],
            [
                "question" => "How can I contact {{appName}} for more information?",
                "answer" => "You can reach out to us anytime via the contact information provided on our website. We are always ready to assist you with any inquiries or support you may need."
            ],
            [
                "question" => "What is the process for receiving treatment at {{appName}}?",
                "answer" => "The treatment process at {{appName}} involves booking an appointment, conducting a checkup, performing the necessary treatment, and prescribing medications or further care. Our streamlined process ensures efficient and effective care."
            ],
            [
                "question" => "How does {{appName}} ensure the quality of its medical services?",
                "answer" => "We utilize cutting-edge medical technology and state-of-the-art facilities to provide the highest quality care. Our dedicated team of healthcare professionals ensures personalized attention and expert medical guidance throughout your treatment journey."
            ]
        ]
            ],
            "testimonials" => [
        "title" => "What Our Patients Say",
        "desc" => "We take pride in the positive feedback we receive from our patients. Here are some of their testimonials:",
        "error_something_went_wrong" => "Something Went wrong!",
        "error_fetch" => "Can't Fetch Testimonials!"
    ],

    "footer" => [
        "copyright" => "© 2024 {{appName}}. All rights reserved",
        "hospital" => [
            "title" => "Hospital",
            "links" => ["About us", "Departments", "Doctors", "Contact Us"]
        ],
        "support" => [
            "title" => "Support",
            "links" => ["Terms of Service", "Legal", "Privacy Policy"]
        ],
        "language" => [
            "title" => "Language"
        ]
    ],

    "clinics_page" => [
        "title" => "Explore Our Clinics",
        "sub_title" => "Connecting you to exceptional healthcare services near you",
        "desc" => "Browse our network of clinics offering personalized care and advanced medical services"
    ],

    "clinic_details_page" => [
        "clinic_details" => "Clinic Details",
        "ambulance_call" => "Call Ambulance",
        "navigate_clinic" => "Navigate to Clinic",
        "clinic_images" => "Clinic Images",
        "not_avl_img" => "Clinic images not available",
        "contact_details" => "Contact Details",
        "opening_hours" => "Opening Hours",
        "opn_hrs_not_avl" => "Opening hours not available",
        "avl_doctor" => "Doctors Availble in {{appName}}",
        "appointment_closed" => "Appointment Closed",
        "app_closed_desc" => "This clinic/hospital is not accepting appointments at the moment."
    ],

    "doctors_page" => [
        "title" => "Our Doctors",
        "sub_title" => "Explore a Multifaceted Team of Healthcare Specialists",
        "desc" => "Experience the ease of finding the right medical expert for your needs with our comprehensive selection of doctors.",
        "search_placeholder" => "Search doctors...",
        "experience" => "Exp {{count}}+ Years",
        "appointments_done" => "{{count}} Appointments Done",
        "not_accepting_appointments" => "Currently Not Accepting Appointments",
        "error_something_went_wrong" => "Something Went wrong!",
        "error_fetch" => "Cant Fetch Doctors!"
    ],

    "doctor_details_page" => [
        "title" => "Doctor Profile",
        "experience_years" => "{{count}}+ Years Of Experience",
        "appointments_done" => "{{count}} Appointments Done",
        "not_taking_appointments" => "Doctor Not Taking Appointments",
        "clinic_not_scheduling" => "Clinic is Not Scheduling Appointments at This Time",
        "make_appointment" => "Make Appointment",
        "select_appointment_type" => "Select Appointment Type",
        "appointment_types" => [
            "opd" => "OPD",
            "video_call" => "Video Call",
            "emergency" => "Emergency"
        ],
        "doctorReviews" => "Reviews & Ratings -"
    ],

    "contact_us_page" => [
        "title" => "Contact Us",
        "desc" => "We are here to assist you with any inquiries or support. Feel free to contact us, and we will get back to you as soon as possible.",
        "address_title" => "Address",
        "call_us_title" => "Call Us",
        "email_us_title" => "Email Us",
        "form" => [
            "name_label" => "Your Name",
            "name_placeholder" => "Your Name",
            "email_label" => "Your Email",
            "email_placeholder" => "Your Email",
            "subject_label" => "Subject",
            "subject_placeholder" => "Subject",
            "message_label" => "Message",
            "message_placeholder" => "Message",
            "submit_button" => "Send Message",
            "name_required" => "Name is required",
            "email_required" => "Email is required",
            "email_invalid" => "Invalid email format",
            "subject_required" => "Subject is required",
            "message_required" => "Message is required",
            "success_title" => "Message Received!",
            "success_desc" => "We have received your message and will get back to you soon."
        ]
    ],

    "user_profile_page" => [
        "title" => "User Profile",
        "first_name_label" => "First Name",
        "last_name_label" => "Last Name",
        "phone_label" => "Phone",
        "email_label" => "Email",
        "gender_label" => "Gender",
        "gender_options" => [
            "male" => "Male",
            "female" => "Female"
        ],
        "dob_label" => "Date of Birth",
        "city_label" => "City",
        "state_label" => "State",
        "postal_code_label" => "Postal Code",
        "address_label" => "Address",
        "update_button" => "Update Profile",
        "toast_success" => "User Details Updated",
        "toast_error_invalid_email" => "Invalid email address"
    ],

    "family_members_page" => [
        "title" => "Family Members",
        "add_new_button" => "Add New Family Member",
        "add_form_title" => "Add New Family Member",
        "first_name_label" => "First Name",
        "first_name_placeholder" => "Enter first name",
        "first_name_required" => "First Name is required",
        "last_name_label" => "Last Name",
        "last_name_placeholder" => "Enter last name",
        "last_name_required" => "Last Name is required",
        "phone_label" => "Phone",
        "phone_placeholder" => "Enter phone number",
        "phone_required" => "Phone is required",
        "phone_invalid" => "Invalid phone number format",
        "gender_label" => "Gender",
        "gender_placeholder" => "Select gender",
        "gender_required" => "Gender is required",
        "gender_options" => [
            "male" => "Male",
            "female" => "Female"
        ],
        "dob_label" => "Date of Birth",
        "dob_required" => "Date of Birth is required",
        "cancel_button" => "Cancel",
        "submit_button" => "Add",
        "toast_add_success" => "Family Member Added",
        "toast_delete_success" => "Family Member Deleted",
        "toast_error_generic" => "Operation failed"
    ],
    "family_member_page" => [
        "title" => "Family Member",
        "first_name_label" => "First Name",
        "first_name_placeholder" => "Enter first name",
        "last_name_label" => "Last Name",
        "last_name_placeholder" => "Enter last name",
        "phone_label" => "Phone",
        "phone_placeholder" => "Enter phone number",
        "email_label" => "Email",
        "email_placeholder" => "Enter email",
        "gender_label" => "Gender",
        "gender_placeholder" => "Select gender",
        "gender_options" => [
            "male" => "Male",
            "female" => "Female"
        ],
        "dob_label" => "Date of Birth",
        "city_label" => "City",
        "city_placeholder" => "Enter city",
        "state_label" => "State",
        "state_placeholder" => "Enter state",
        "postal_code_label" => "Postal Code",
        "postal_code_placeholder" => "Enter postal code",
        "address_label" => "Address",
        "address_placeholder" => "Enter address",
        "update_button" => "Update Profile",
        "vitals_title" => "Vitals",
        "date_label" => "Date",
        "toast_success" => "Family Member Updated",
        "toast_error_generic" => "Failed to update family member"
    ],

    "vitals_page" => [
        "Blood-Pressure" => "Blood Pressure",
        "Blood-Pressure-desc" => "Blood Pressure details and history",
        "Blood-Sugar" => "Blood Sugar",
        "Blood-Sugar-desc" => "Blood Sugar levels and insights",
        "Temperature" => "Temperature",
        "Temperature-desc" => "Body temperature monitoring",
        "Weight" => "Weight",
        "Weight-desc" => "Weight tracking",
        "SpO2" => "Oxygen Level",
        "SpO2-desc" => "Oxygen saturation levels",
        "no_family_member" => "You Dont Added Any family members"
    ],

    "appointments_page" => [
        "title" => "Appointments",
        "no_appointments_alert_title" => "Appointments Not Found!",
        "no_appointments_alert_description" => "You have no appointments. Book one now!",
        "make_appointment_button" => "Make Appointment",
        "filter_steps" => [
            "All" => "All",
            "Upcoming" => "Upcoming",
            "Pending" => "Pending",
            "Confirmed" => "Confirmed",
            "Rejected" => "Rejected",
            "Completed" => "Completed",
            "Rescheduled" => "Rescheduled",
            "Cancelled" => "Cancelled",
            "Visited" => "Visited",
            "Closed" => "Closed"
        ],
        "card" => [
            "name_label" => "Name",
            "id_label" => "ID",
            "time_label" => "Time",
            "doctor_label" => "Doctor",
            "rebook_button" => "Rebook"
        ]
    ],

    "newAppointment" => [
        "selectDoct" => "Please select doctor and Appointment type before proceeding.",
        "selectDate" => "Please select date  & time slot before proceeding",
        "selectFamilyMember" => "Please select family member before proceeding",
        "timeSlotes" => "Time Slots",
        "noTimeSlotes" => "  Sorry , no available time slotes ware found for the selected date.",
        "addFamilyMember" => "Add Family Member",
        "oneStepAway" => " Only One Step Away",
        "payAndBook" => "Pay & Book Appointment",
        "appType" => "Appointment Type",
        "appFee" => "Appointment Fee",
        "appliedCoupon" => "Applied Coupon",
        "coupDiscount" => "Discount",
        "coupCodePlaceholder" => "Apply Coupon",
        "apply" => "Apply",
        "payNow" => "Pay Now",
        "payAtHospital" => "Pay at Hospital",
        "payWallet" => "Pay from Wallet",
        "avlBlc" => "Available Balance",
        "bookNow" => "Book Now",
        "payAndBookTest" => "Pay ",
        "selectedCoupon" => "Selected Coupon",
        "discount" => "Discount"
    ],

    "appointmentDetails" => [
        "title" => "Appointment",
        "reviewDoctor" => "Review Doctor",
        "queueNumber" => "Queue Number",
        "happyPatients" => "Happy Patients",
        "checkIn" => "Check-In",
        "patient" => "Patient:",
        "joinMeeting" => "Join Meeting",
        "prescriptions" => "Prescriptions -",
        "downloadPrescription" => "Download Prescription",
        "prescriptionsNotFound" => "Prescriptions Not Found!",
        "patientFiles" => "Patient Files",
        "filesNotFound" => "Files Not Found!",
        "paymentStatus" => "Payment Status",
        "paymentId" => "Payment Id",
        "downloadInvoice" => "Download Invoice",
        "navigate" => "Navigate to clinic",
        "appointmentCancellation" => "Appointment Cancellation",
        "initiateCancellation" => "Click Here to Initiate Cancellation Request",
        "deleteCancellation" => "Click Here to Delete Cancellation Request",
        "currentStatus" => "Current status - {{status}}",
        "requestHistory" => "Request History",
        "dialogModal" => [
            "cancelAppointment" => "Cancel Appointment ?",
            "deleteCancellationRequest" => "Delete Cancellation Request ?",
            "confirmCancel" => "Are you sure, you want to cancel this appointment ?",
            "confirmDelete" => "Are you sure, you want to delete cancellation request ?"
        ]
    ],

    "appointmentSuccess" => [
        "appointmentId" => "Appointment ID",
        "joinMeeting" => "Join Meeting",
        "successMessage" => "Your Appointment Booked successfully!",
        "opdInstruction" => "Visit the clinic and scan the provided QR code to instantly generate your appointment queue number",
        "videoInstruction" => "Click join meeting or scan the QR code to join the meeting.",
        "dateTime" => "Date & Time",
        "patientName" => "Patient Name",
        "addToCalendar" => "Add to Calender"
    ],

    "files_page" => [
        "searchFiles" => "Search Files...",
        "noFiles" => " The files are currently unavailable. You will be able to access them once the doctor uploads the necessary documents"
    ],

    "prescription_page" => [
        "searchFiles" => "Search Prescriptions ...",
        "noFiles" => "Prescriptions are currently unavailable. You will be able to access them once the doctor uploads them."
    ],

    "wallet_page" => [
        "currBal" => "Current Balance",
        "addMoney" => "Add Money",
        "txnHistory" => "Transaction History",
        "noTxn" => " No Transaction found",
        "title" => "Add Money To Your Wallet",
        "enterAmount" => "Enter Amount",
        "noPaymentMethod" => "No active payment methods!",
        "successMessage" => "Success!",
        "errorMessage" => "Something went wrong!",
        "amountCredited" => "Amount Credited To Your Wallet",
        "amountDebited" => "Amount Debited From Your Wallet",
        "transactionId" => "Transaction ID",
        "paymentId" => "Payment ID: {id}"
    ],

    "addMoney" => [
        "title" => "Add Money To Your Wallet",
        "enterAmount" => "Enter Amount",
        "placeholder" => "Enter amount in rupees",
        "noPaymentMethod" => "No active payment methods!",
        "successMessage" => "Success!",
        "errorMessage" => "Something went wrong!",
        "specificError" => "{message}"
    ],

    "searchPage" => [
        "search" => "Search",
        "searchPlaceholder" => "Search for doctors, clinics, and specializations..",
        "noResults" => "No results found for '{{query}}'",
        "searchResults" => "Search Results for '{{query}}'",
        "searching" => "Searching...",
        "error" => "Error occurred while searching"
    ],

    "firebaseLogin" => [
        "welcome" => "Welcome",
        "welcomeBack" => "Welcome Back!",
        "loginDescription" => "We provide the best and most affordable healthcare services.",
        "mobileNumber" => "Mobile number",
        "enterPhoneDescription" => "We'll send you a verification code",
        "phonePlaceholder" => "Enter your phone number",
        "continue" => "Continue",
        "verifying" => "Verifying...",
        "termsText" => "By continuing, you agree to our ",
        "termsOfUse" => "Terms of Use",
        "privacyPolicy" => "Privacy Policy",
        "createAccount" => "Create an account",
        "newUser" => "New here?",
        "enterOtp" => "Verify Your Phone",
        "otpSentTo" => "Code sent to",
        "login" => "Verify & Login",
        "verifyingOtp" => "Verifying...",
        "resendOtp" => "Resend Code",
        "resendIn" => "Resend in",
        "didntReceive" => "Didn't receive code?",
        "useDifferentPhone" => "Change Phone Number",
        "otpSentSuccess" => "Code Sent Successfully",
        "otpSentDescription" => "Please check your phone for the verification code.",
        "otpError" => "Failed to Send Code",
        "otpErrorDescription" => "Please try resending the code",
        "otpInvalid" => "Please enter a valid 6-digit code",
        "invalidOtpTitle" => "Invalid Code",
        "invalidOtpDescription" => "The code you entered is incorrect. Please try again.",
        "loginSuccessTitle" => "Login Successful",
        "loginError" => "Login failed. Please try again.",
        "phoneRequired" => "Phone number is required",
        "phoneTooShort" => "Phone number is too short",
        "phoneTooLong" => "Phone number is too long",
        "phoneNotExist" => "Phone number not registered. Please sign up first.",
        "genericError" => "Something went wrong. Please try again.",
        "otpResentSuccess" => "Verification code resent successfully.",
        "otpResentFailed" => "Failed to resend code. Please try again.",
        "secureLogin" => "Secure Login",
        "secure" => "Secure",
        "fast" => "Fast",
        "verified" => "Verified"
    ],

    "signup" => [
        "title" => "Sign Up",
        "description" => "Join us for the best healthcare services.",
        "form" => [
            "first_name_label" => "First Name",
            "first_name_required" => "First name is required",
            "last_name_label" => "Last Name",
            "last_name_required" => "Last name is required",
            "phone_label" => "Phone Number",
            "phone_required" => "Phone number is required",
            "phone_pattern" => "Phone number must be 10 digits",
            "gender_label" => "Gender",
            "gender_required" => "Please select your gender",
            "gender_options" => [
                "male" => "Male",
                "female" => "Female",
                "other" => "Other"
            ],
            "gender_placeholder" => "Select gender",
            "dob_label" => "Date of Birth",
            "dob_required" => "Date of Birth is required",
            "email_label" => "Email Address",
            "email_pattern" => "Invalid email address",
            "otp_label" => "Enter OTP",
            "submit_button_step1" => "Get OTP",
            "submit_button_step2" => "Sign Up"
        ],
        "toast" => [
            "otp_sent_title" => "OTP Sent",
            "otp_sent_description" => "Please check your phone for the OTP.",
            "otp_error_title" => "Error",
            "otp_error_description" => "Failed to send OTP. Please try again.",
            "otp_invalid_description" => "Please Enter valid OTP.",
            "phone_exists_title" => "Phone number already exists!",
            "signup_success_title" => "Signup Successful",
            "signup_success_description" => "Welcome {{f_name}} {{l_name}}",
            "otp_missing_title" => "Please Enter OTP!",
            "signup_failed_description" => "Signup failed",
            "invalid_otp_description" => "Invalid OTP"
        ],
        "terms_text" => "By signing up, you agree to our",
        "terms_of_use" => "Terms of Use",
        "privacy_policy" => "Privacy Policy",
        "login_link" => "Already have an account? Log in"
    ],

    "dept_page" => [
        "title" => "{{name}} Department",
        "subtitle" => "Explore a Multifaceted Team of",
        "highlightedSubtitle" => "{{name}} Department",
        "description" => "Experience the ease of finding the right medical expert for your needs with our comprehensive selection of doctors.",
        "otherDepartments" => "Explore Other Departments",
        "noDoctorsMessage" => "We apologize for the inconvenience, but there are no doctors currently available in the {{deptName}} department. Please check back later or consider a different department. If you require immediate assistance, our support team is here to help you with any urgent concerns. Thank you for your understanding and patience."
    ],

    "blogs_page" => [
        "title" => "Blogs",
        "description" => "Explore our latest articles, insights, and updates across various topics.",
        "no_blogs" => "No Blogs Found",
        "no_blogs_description" => "There are currently no featured blog posts available.",
        "error_fetch" => "Can't Fetch Blogs!",
        "recent_post" => "Recent Posts",
        "author_details" => "Author Details"
    ],

    "blog_page" => [
        "title" => "Blog"
    ],

    "lab-component" => [
        "title" => "Our Labs",
        "title1" => "Top Labs in {{city}}",
        "desc" => "Discover specialized healthcare services tailored to your needs, all in one place",
        "see-all" => "See All Labs"
    ],

    "labs_page" => [
        "title" => "Explore Our Labs",
        "sub_title" => "Connecting you to exceptional healthcare services near you",
        "desc" => "Browse our network of labs offering personalized care and advanced medical services",
        "search_placeholder" => "Search labs...",
        "bookings" => "Bookings",
        "review" => "review",
        "reviews" => "reviews",
        "email" => "Email"
    ],

    "lab_details_page" => [
        "lab_details" => "Lab Details",
        "ambulance_call" => "Call Ambulance",
        "navigate_lab" => "Navigate to Lab",
        "lab_images" => "Lab Images",
        "not_avl_img" => "Lab images not available",
        "contact_details" => "Contact Details",
        "opening_hours" => "Opening Hours",
        "opn_hrs_not_avl" => "Opening hours not available",
        "avl_doctor" => "Available Test in {{appName}}",
        "appointment_closed" => "Appointment Closed",
        "app_closed_desc" => "This lab is not accepting booking at the moment."
    ],

    "lab_bookings_page" => [
        "title" => "Your Lab Bookings",
        "no_bookings_alert_title" => "No Lab Bookings Found",
        "make_booking_button" => "Book a Lab Test",
        "noInvoicesAvailable" => "No invoices available",
        "navigate" => "Navigate to Lab",
        "files" => "Files / Test Reports",
        "addReview" => "Add Review",
        "reviews" => "Reviews",
        "testsIncluded" => "Tests Included",
        "bookingId" => "Booking ID",
        "rebookingFailed" => "Rebooking failed",
        "card" => [
            "name_label" => "Patient",
            "test_label" => "Test",
            "lab_label" => "Lab",
            "payment_label" => "Payment Status",
            "rebook_button" => "Rebook"
        ],
        "payment_status" => [
            "paid" => "Paid",
            "not_paid" => "Not Paid",
            "refunded" => "Refunded",
            "unpaid" => "Unpaid",
            "unknown" => "Unknown"
        ]
    ],

    "labCart" => [
        "title" => "Lab Cart",
        "cartDetails" => "Cart Details",
        "your_lab_cart_empty" => "Your lab cart is empty",
        "your_selected_tests" => "Your Selected Tests",
        "remove" => "Remove",
        "subtotal" => "Subtotal",
        "tests" => "tests",
        "service_charge" => "Service Charge",
        "free" => "Free",
        "tax_18" => "Tax (18%)",
        "continue" => "Continue",
        "select_date" => "Select Date",
        "select_patient" => "Select Patient / Family Member",
        "pay_and_book" => "Pay And Book Lab Test",
        "total_amount" => "Total Amount",
        "total_payable_amount" => "Total Payable Amount",
        "lab" => "Lab",
        "Date" => "Date",
        "Removed from cart" => "Removed from cart",
        "Something went wrong" => "Something went wrong",
        "No tests added yet" => "No tests added yet",
        "Payment Successful" => "Payment Successful",
        "Lab Test booked successfully" => "Lab Test booked successfully",
        "Failed to book" => "Failed to book",
        "Failed to remove" => "Failed to remove",
        "Subtests" => "Subtests",
        "bookNow" => "Book Now"
    ],"cart" => [
        "title" => "Cart",
        "select_address" => "Select Address",
        "payment" => "Payment",
        "products" => "Products",
        "lab_tests" => "Lab Tests",
        "empty_product_cart" => "Your product cart section is empty! Explore our range of products and find the care you need.",
        "empty_lab_cart" => "Your lab tests cart section is empty! Explore our range of services and find the care you need.",
        "total" => "Total",
        "place_order" => "Place Order",
        "qty" => "Qty",
        "remove" => "Remove",
        "mrp" => "MRP",
        "price" => "PRICE",
        "tax" => "TAX",
        "discount" => "Discount",
        "delivery_address" => "Delivery Address",
        "your_wallet_balance" => "Your Wallet Balance",
        "use_wallet_balance" => "Use Wallet Balance",
        "pay_now" => "Pay Now",
        "cod" => "COD",
        "product_delivery_details" => "Product Delivery Details & Timing",
        "delivery" => "Delivery",
        "delivery_charges_20" => "Delivery Charges - ₹20.0",
        "delivery_desc" => "Delivery - Expect your delivery within 24 to 28 hours after your order is successful.",
        "home_time" => "Home Time (8 AM to 8 PM)",
        "office_time" => "Office Time (10 AM to 5 PM)",
        "emergency_delivery" => "Emergency Delivery",
        "emergency_charges" => "Delivery Charges - ₹50.0",
        "emergency_desc" => "Urgent Dispatch: Your Product, Delivered Rapidly Within Hours",
        "your_wallet_amount" => "Your Wallet Amount",
        "used_wallet_amount" => "Used Wallet Amount",
        "total_payable_amount" => "Total Payable Amount",
        "back" => "Back",
        "order_now" => "Order Now"
    ],

    "common" => [
        "closed" => "Closed",
        "na" => "N/A",
        "opd" => "OPD",
        "video_consultant" => "Video Consultant",
        "emergency" => "Emergency",
        "doctor" => "Doctor",
        "happy_patients" => "Happy Patients",
        "not_paid" => "Not Paid",
        "loading" => "Loading...",
        "load_more" => "Load More",
        "try_again" => "Try Again",
        "success" => "Success",
        "error" => "Error",
        "remove" => "Remove",
        "or" => "or"
    ],

    "blogs" => [
        "title" => "Blogs",
        "not_available" => "Blogs not available.",
        "author_not_available" => "Author not available"
    ],

    "blog" => [
        "not_available" => "Blog not available."
    ],

    "error_page" => [
        "title" => "500 Internal Server Error",
        "code" => "500",
        "heading" => "Internal Server Error",
        "message" => "Oops! Something went wrong on our end. We are currently working on fixing the issue.",
        "description" => "Please try refreshing the page, or you can return to the homepage.",
        "try_again" => "Try Again"
    ],

    "not_found_page" => [
        "title" => "Something is not right...",
        "description" => "The page you are trying to access does not exist. You may have mistyped the URL or the page has been moved to another location. If you believe this is an error, please contact our support team. We apologize for any inconvenience caused and appreciate your understanding.",
        "back_home" => "Get back to home page"
    ],

    "error" => [
        "something_went_wrong" => "Something Went wrong!",
        "cant_fetch_doctors" => "Can't Fetch Doctors!",
        "cant_fetch_tests" => "Can't Fetch Tests!"
    ],

    "components" => [
        "testsByLab" => [
            "Report in" => "Report in",
            "day" => "day",
            "days" => "days",
            "Removing" => "Removing...",
            "Adding" => "Adding...",
            "Add to Cart" => "Add to Cart",
            "Subtests Included" => "Subtests Included",
            "Added" => "Added",
            "View Cart" => "View Cart",
            "Cart updated successfully" => "Cart updated successfully",
            "Failed to update cart" => "Failed to update cart"
        ],

        "addReview" => [
            "lab_rating_title" => "Lab Test Rating",
            "doctor_rating_title" => "Doctor Rating",
            "write_feedback_placeholder" => "Write your feedback",
            "close" => "Close",
            "add_review" => "Add Review",
            "review_added_success" => "Review Added Successfully"
        ],

        "address" => [
            "add_new_address" => "Add New Address",
            "name_label" => "Name",
            "name_placeholder" => "Enter your name",
            "phone_label" => "Phone",
            "phone_placeholder" => "Enter your phone number",
            "flat_no_label" => "Flat No",
            "flat_no_placeholder" => "Enter your flat number",
            "apartment_label" => "Apartment Name",
            "apartment_placeholder" => "Enter your apartment name",
            "area_label" => "Area/Street",
            "area_placeholder" => "Enter your area/street",
            "landmark_label" => "Landmark",
            "landmark_placeholder" => "Enter a landmark",
            "city_label" => "City",
            "city_placeholder" => "Enter your city",
            "pincode_label" => "Pincode",
            "pincode_placeholder" => "Enter your pincode",
            "cancel" => "Cancel",
            "submit" => "Submit"
        ],

        "notification" => [
            "title" => "Notifications",
            "no_notifications" => "No notifications available",
            "mark_all_read" => "Mark all as read",
            "clear_all" => "Clear all"
        ],

        "search" => [
            "placeholder" => "Search...",
            "no_results" => "No results found",
            "searching" => "Searching..."
        ],

        "wallet" => [
            "current_balance" => "Current Balance",
            "add_money" => "Add Money",
            "transaction_history" => "Transaction History",
            "no_transactions" => "No transactions found",
            "credited" => "Credited",
            "debited" => "Debited",
            "transaction_id" => "Transaction ID"
        ],

        "userProfile" => [
            "edit_profile" => "Edit Profile",
            "save_changes" => "Save Changes",
            "cancel" => "Cancel",
            "update_success" => "Profile updated successfully",
            "update_failed" => "Failed to update profile"
        ],

        "familyMembers" => [
            "add_member" => "Add Family Member",
            "edit_member" => "Edit Family Member",
            "delete_member" => "Delete Family Member",
            "member_added" => "Family member added successfully",
            "member_updated" => "Family member updated successfully",
            "member_deleted" => "Family member deleted successfully"
        ],

        "loading" => [
            "please_wait" => "Please wait...",
            "loading_data" => "Loading data..."
        ],

        "notAvailable" => [
            "not_available" => "Not Available",
            "no_data" => "No data available"
        ],

        "locationSelector" => [
            "select_location" => "Select Location",
            "current_location" => "Use Current Location",
            "search_location" => "Search location...",
            "no_locations_found" => "No locations found"
        ],

        "loginModal" => [
            "login_title" => "Login",
            "signup_title" => "Sign Up",
            "phone_label" => "Phone Number",
            "otp_label" => "Enter OTP",
            "send_otp" => "Send OTP",
            "verify_otp" => "Verify OTP",
            "resend_otp" => "Resend OTP",
            "login_success" => "Login successful",
            "login_failed" => "Login failed"
        ],

        "qrCode" => [
            "scan_qr" => "Scan QR Code",
            "download_qr" => "Download QR Code"
        ],

        "profilePicture" => [
            "upload_photo" => "Upload Photo",
            "change_photo" => "Change Photo",
            "remove_photo" => "Remove Photo",
            "upload_success" => "Photo uploaded successfully",
            "upload_failed" => "Failed to upload photo"
        ],

        "whatsappBtn" => [
            "chat_with_us" => "Chat with us on WhatsApp"
        ],

        "doctorReviews" => [
            "no_reviews" => "No reviews yet",
            "write_review" => "Write a review",
            "rating" => "Rating",
            "reviews" => "Reviews"
        ],

        "testimonials" => [
            "patient_testimonials" => "Patient Testimonials",
            "what_patients_say" => "What Our Patients Say"
        ],

        "departments" => [
            "all_departments" => "All Departments",
            "view_doctors" => "View Doctors"
        ],

        "doctors" => [
            "book_appointment" => "Book Appointment",
            "view_profile" => "View Profile",
            "available" => "Available",
            "not_available" => "Not Available"
        ],

        "clinics" => [
            "view_details" => "View Details",
            "book_now" => "Book Now"
        ],

        "labs" => [
            "view_tests" => "View Tests",
            "book_test" => "Book Test"
        ],

        "contactDetails" => [
            "email" => "Email",
            "phone" => "Phone",
            "address" => "Address",
            "working_hours" => "Working Hours"
        ],

        "maps" => [
            "get_directions" => "Get Directions",
            "view_on_map" => "View on Map"
        ],

        "patients" => [
            "patient_info" => "Patient Information",
            "medical_history" => "Medical History"
        ],

        "recentPosts" => [
            "recent_posts" => "Recent Posts",
            "read_more" => "Read More"
        ],

        "featuredBlogs" => [
            "featured_blogs" => "Featured Blogs",
            "explore_all" => "Explore All"
        ]
        ],
        
];

  
    // Default JSON for User App
    private $user_app_default_lng_json = [
        "appointment" => "Appointment",
        "appointments" => "Appointments",
        "vitals" => "Vitals",
        "prescription" => "Prescription",
        "profile" => "Profile",
        "family_member" => "Family Member",
        "wallet" => "Wallet",
        "notification" => "Notification",
        "contact_us" => "Contact Us",
        "files" => "Files",
        "search" => "Search",
        "menu" => "Menu",
        "home" => "Home",
        "department" => "Department",
        "swipe_more" => "Swipe More >>",
        "best_doctors_in" => "Best Doctors in @city",
        "best_clinic_in" => "Best Clinic in @city",
        "view_all" => "View All",
        "no_doctors_found_in" => "No Doctors found in @city!",
        "update" => "Update",
        "update_app_prompt_body_1" => "New version is available, please update the app.",
        "update_app_prompt_body_2" => "Sorry we are currently not supporting the old version of the app please update with new version.",
        "cancel" => "Cancel",
        "sorry!" => "Sorry!!",
        "tech_issue_prompt_body" => "We are facing some technical issues. our team trying to solve problems. hope we will come back very soon.",
        "welcome!" => "Welcome!",
        "user" => "User",
        "search....." => "Search.....",
        "location_permission_message" => "This app requires location permission to function properly.",
        "location_permission_description" => "To access location features, you need to allow location permission from the app settings. Please enable it to continue.",
        "open_settings" => "Open Settings",
        "search_city" => "Search City",
        "no_data_found!" => "No Data Found!",
        "booking_done" => "Booking Done @count",
        "doctors" => "Doctors",
        "search_placeholder_doctor_page" => "Search Doctors, Clinic, Specialization, Department",
        "showing_doctors_from" => "Showing doctors from",
        "department_sm" => " department",
        "experience_year" => "Experience @count Year",
        "not_accepting_appointments" => "Currently not accepting appointments",
        "appointments_done" => "@count Appointments Done",
        "book_now" => "Book Now",
        "rating_review_text" => "@rating (@count Review)",
        "login" => "Login",
        "enter_credential_to_login" => "Enter Credential to login",
        "enter_phone_number" => "Enter Phone Number",
        "enter_valid_number" => "Enter valid number",
        "submit" => "Submit",
        "otp_sent_to" => "OTP sent to @number",
        "enter_valid_otp" => "Enter valid otp",
        "resend_text" => "Resend @seconds(s)",
        "register_text" => "Register @phoneNumber",
        "enter_first_name" => "Enter first name",
        "first_name_label" => "First Name*",
        "enter_last_name" => "Enter Last name",
        "last_name_label" => "Last Name*",
        "verified" => "Verified",
        "something_went_wrong" => "Something Went Wrong",
        "successfully_registered" => "Successfully Registered",
        "logged_in" => "Logged in",
        "otp" => "OTP",
        "family_members" => "Family Members",
        'my_appointment' => "My Appointment",
        "share" => "Share",
        "testimonials" => "Testimonials",
        "about_us" => "About Us",
        "privacy_policy" => "Privacy Policy",
        "terms_condition" => "Terms & Condition",
        "app_version" => "Version @vnumber",
        "member" => "Member",
        "hello_user" => "Hello @uname",
        "membership_since_date" => "Membership since @date",
        "login/signup" => "Login/Signup",
        "logout" => "Logout",
        "appointment_booking" => "Appointment Booking",
        "lab_booking" => "Lab Booking",
        "my_booking" => "My Booking",
        "upcoming" => "Upcoming",
        "closed" => "Closed",
        "appointment_name" => "Name: @app_name_id",
        "name:" => "Name:",
        "time:" => "Time:",
        "doctor_name" => "Doctor @name",
        "rebook" => "Rebook",
        "Pending" => "Pending",
        "Confirmed" => "Confirmed",
        "Rejected" => "Rejected",
        "Completed" => "Completed",
        "Rescheduled" => "Rescheduled",
        "Cancelled" => "Cancelled",
        "Visited" => "Visited",
        "month_jan" => "JAN",
        "month_feb" => "FEB",
        "month_mar" => "MARCH",
        "month_apr" => "APRIL",
        "month_may" => "MAY",
        "month_jun" => "JUN",
        "month_jul" => "JULY",
        "month_aug" => "AUG",
        "month_sep" => "SEP",
        "month_oct" => "OCT",
        "month_nov" => "NOV",
        "month_dec" => "DEC",
        "OPD" => "OPD",
        "Video Consultant" => "Video Consultant",
        "Video Call" => "Video Call",
        "Emergency" => "Emergency",
        "success" => "success",
        "Wallet" => "Wallet",
        "current_balance" => "Current Balance",
        "add_money_btn" => "+ Add Money",
        "transaction_history" => "Transaction History",
        "no_transaction_found" => "No transaction found",
        "Credited" => "Credited",
        "Debited" => "Debited",
        "transaction_id" => "Transaction Id - @id",
        "add_money" => "Add Money",
        "enter_valid_amount" => "Enter Valid Amount",
        "amt_des" => "Enter Amount Between @am_1 To @am_2",
        "amount" => "Amount",
        "process" => "Process",
        "no_active_payment_gateway" => "No active payment gateway",
        "please_fill_the_details" => "Please fill the details",
        "enter_name" => "Enter name",
        "name*" => "Name*",
        "enter_address" => "Enter address",
        "address*" => "Address*",
        "enter_city" => "Enter City",
        "city*" => "City*",
        "enter_state" => "Enter State",
        "state*" => 'State*',
        "enter_country" => "Enter Country",
        "country*" => "Country*",
        "proceed_to_pay" => "Proceed To Pay",
        "book_appointment" => "Book Appointment",
        "about" => "About",
        "date" => "Date",
        "time" => "Time",
        "not_accepting_appointment" => "Sorry, the doctor is not accepting appointments at this time.",
        "add_select_family_member" => "Add/Select Family Member",
        "add_new" => "Add New",
        "choose_date_and_time" => "Choose Date And Time",
        "no_available_time_slot" => "Sorry, no available time slots were found for the selected date",
        "patient" => "Patient",
        "register_new_member" => "Register New Member",
        "save" => "Save",
        "only_one_step_away" => "Only one step away\nPay and book your appointment.",
        "doctor:" => "Doctor:",
        "patient:" => "Patient:",
        "appointment:" => "Appointment:",
        "date_time:" => "Date - Time:",
        "appointment_fee:" => "Appointment Fee:",
        "coupon_off" => "Coupon (@value)% OFF",
        "tax_value" => "Tax (@tax)%",
        "total_amount" => "Total Amount:",
        "pay_now" => "Pay Now",
        "pay_at_hospital" => "Pay At Hospital",
        "pay_from_wallet_av" => "Pay From Wallet (Available Balance @walletAmount)",
        "insufficient_amount_in_your_wallet" => "Insufficient amount in your wallet",
        "tap_here_to_recharge_wallet" => "Tap here to recharge wallet",
        "pay_and_book_amt" => "Pay @totalAmount & Book Appointment",
        "the_time_has_passed" => "The time has passed, please choose the different time",
        "enter_coupon_code_if_any" => "Enter Coupon Code IF Any",
        "coupon_code" => "Coupon Code",
        "apply" => "Apply",
        "remove" => "Remove",
        "coupon_removed" => "Coupon Removed",
        "fee_amt" => "Fee @fee",
        "check_in" => "Check-In",
        "queue_number_value" => "Queue Number - @number",
        "appointment_id" => "Appointment #@id",
        "payment_status_value" => "Payment Status @status",
        "Unpaid" => "Unpaid",
        "Paid" => "Paid",
        "Partially Paid" => "Partially Paid",
        "download_invoice" => "Download Invoice",
        "make_direction_to_clinic_location" => "Make direction to clinic location",
        "review" => "Review",
        "click_here_to_give_doctor_review" => "Click here to give doctor review",
        "appointment_cancellation" => "Appointment Cancellation",
        "to_create_cancellation_request" => "Click here to create cancellation request",
        "to_delete_cancellation_request" => "Click here to delete cancellation request",
        "current_status_value" => "Current Status - @value",
        "doctor_review" => "Doctor Review",
        "review_to_doctor_value" => "Give review to @doctName",
        "cancel_this_appointment_box" => "Are you sure want to cancel this appointment",
        "no" => "No",
        "yes" => "Yes",
        "delete" => "Delete",
        "delete_the_cancellation_request_box" => "Are you sure want to delete the cancellation request",
        "cancellation_request_history" => "Cancellation Request History",
        "patient_files" => "Patient Files",
        "click_here_to_check_the_patient_files" => "Click here to check the patient files",
        "no_prescription_found" => "No Prescription Found!",
        "prescription_id" => "Prescription #@id",
        "click_here_to_download_prescription" => "Click here to download prescription",
        "appointment_type" => "Appointment - @type",
        "date_checkin" => "Date - @date",
        "time_checkin" => "Time - @time",
        "checkin_desc" => "Visit the clinic and scan the provided QR code to instantly generate your appointment queue number.",
        "Initiated" => "Initiated",
        "Approved" => "Approved",
        "Processing" => "Processing",
        "clinics" => "Clinics",
        "clinic" => "Clinic",
        "search_clinic" => "Search Clinic",
        "Call" => "call",
        "call" => "Call",
        "whatsapp" => "Whatsapp",
        "email" => "Email",
        "map" => "Map",
        "ambulance" => "Ambulance",
        "current_not_accepting_appointment" => "Current not accepting appointment",
        "description" => "Description",
        "opening_hours" => "Opening Hours",
        "search_report" => "Search Report",
        "address_value" => "Address: @value",
        "phone_value" => "Phone: @value",
        "whatsapp_value" => "Whatsapp: @value",
        "member_since_value" => "Member since @value",
        "edit_profile" => "Edit Profile",
        "name" => "Name",
        "length_must_be_grater_then_5_latter" => "Length must be grater then 5 latter",
        'first_name' => "First Name",
        'last_name' => "Last Name",
        "enter_a_valid_email_address" => "Enter a valid email address",
        "dob" => "DOB",
        "gender" => "Gender",
        "select_gender*" => "Select Gender*",
        "select_gender" => "Select Gender",
        'Male' => "Male",
        "Female" => "Female",
        "Other" => "Other",
        "delete_profile" => "Delete Profile",
        "delete_profile_desc" => "Are you sure want to delete Your Profile?\nYou can't undo this action",
        "warning" => "Warning",
        "warning_delete_desc" => "By deleting this profile all details and points will also be deleted",
        "successfully_deleted" => "successfully deleted",
        "no_family_member_found_des" => "No family member found. Click the + button at the bottom to add a new one.",
        "add_new_family_member" => "Add New Family Member",
        "update_new_family_member" => "Update New Family Member",
        "sure_delete_fm" => "Are you sure want to delete @name from family members list.",
        "Blood Pressure" => "Blood Pressure",
        "Sugar" => "Sugar",
        "Weight" => "Weight",
        "Temperature" => "Temperature",
        "SpO2" => "SpO2",
        "member_name_value" => "Name - @value",
        "date_value" => "Date @value",
        "select_vital*" => "Select Vital*",
        "select_vital" => "Select Vital",
        "bp_systolic" => "BP Systolic",
        "bp_diastolic" => "BP Diastolic",
        "random" => "Random",
        "fasting" => "Fasting",
        "register_new_family_member" => "Register New Family Member",
        "add_blood_pressure" => "Add Blood Pressure",
        "systolic_(mmHg)" => "Systolic (mmHg)",
        "enter_value" => "Enter value",
        "diastolic_(mmHg)" => "Diastolic (mmHg)",
        "add_weight" => "Add Weight",
        "weight_(KG)" => "Weight (KG)",
        "add_temperature" => "Add Temperature",
        "temp_(F)" => "Temp (F)",
        "add_SpO2" => "Add SpO2",
        "SpO2_(%)" => "SpO2 (%)",
        "add_sugar" => "Add Sugar",
        "sugar_random_(Mg/dl)" => "Sugar Random (Mg/dl)",
        "sugar_fasting_(Mg/dl)" => "Sugar Fasting (Mg/dl)",
        "fill_at_least_desc" => "Please fill at least one filed",
        "delete_record_desc" => "Are you sure want to delete this record",
        "select_date" => "Select Date",
        "Blood Pressure (mmHg" => "Blood Pressure (mmHg)",
        "Sugar (Mg/dl)" => "Sugar (Mg/dl)",
        "Temperature (F)" => "Temperature (F)",
        "SpO2 (%)" => "SpO2 (%)",
        "bp_systolic_value" => "BP Systolic - @value mmHg",
        "bp_diastolic_value" => "BP Diastolic - @value mmHg",
        "sugar_random_value" => "Sugar Random - @value Mg/dl",
        "sugar_fasting_value" => "Sugar Fasting - @value Mg/dl",
        "weight_value" => "Weight - @value KG",
        'temp_value' => "Temp - @value F",
        "spO2_value" => "SpO2 - @value %",
        "payment_success_dont_close_the_app" => "Payment success don't close the app",
        "payment_error" => "Payment error",
        "payment" => "Payment",
        "payment_success" => "Payment Success",
        "payment_return_id_desc" => "Please wait, while we are processing order id: @id",
        "knock_knock" => "knock knock",
        "share_app_with_friends" => "Share app with friends",
        "sorry_it_error!" => "Sorry it's error!",
        "no_internet_check_desc" => "No internet, please check your internet connection and try again!",
        "next" => "Next",
        "payment_failed" => "Payment Failed",
        "pathology_lab" => "Pathology Lab",
        "best_path_in" => "Best Pathology Lab in @city",
        "search_path_lab" => "Search Pathology or Lab Test",
        "lab_test" => "Lab Test",
        "current_not_accepting_booking" => "Current not accepting booking",
        "only_one_step_away_lab" => "Only one step away\nPay and book your lab test.",
        'bool_lab_test' => "Book Lab Test",
        "pay_and_book_amt_lab" => "Pay @totalAmount & Book Lab Test",
        "booking_id" => "Booking #@id",
        'search_test' => "Search Test",
        "tests_included" => "@count Tests Included",
        "add_to_cart" => "Add to cart",
        "test_included" => "Test Included",
        "item_in_cart" => "@count Item in cart",
        "items_in_cart" => "@count Items in cart",
        "view" => "View",
        "total_and_pay" => "Total @amt & Book Now",
        "remove_x" => "Remove X",
        "remove_from_cart" => "Are you sure want to remove @title from cart",
        "payment_summary" => "Payment Summary",
        "final_total" => "Final Total",
        "pay_at_lab" => "Pay At Lab",
        "lab_appointment" => "Lab Appointment",
        'booking_cancellation' => "Booking Cancellation",
        "patient_name" => "Patient - @name",
        "test_included_count" => "Test Included @count",
        "click_here_review" => "Click here to give your review",
        "give_review_to" => "Give review to @labName",
        'lab_added_to_cart' => "Lab added to cart",
        "invoice_id" => "Invoice Id #@id",
         "failed_to_create_order"=>"Failed to create order",
      "add_money_to_you_wallet_successfully"=>"Add money to you wallet successfully",
      "appointment_booked_successfully"=>"Appointment booked successfully",
      "lab_test_booked_successfully"=>"Lab Test Booking Successfully",
            "failed_to_create_order"=>"Failed to create order",
      "add_money_to_you_wallet_successfully"=>"Add money to you wallet successfully",
      "appointment_booked_successfully"=>"Appointment booked successfully",
      "lab_test_booked_successfully"=>"Lab Test Booking Successfully",
      "talk_with_ai"=>"Talk with AI",
      "ai_chat_card_desc"=>"Get the best doctor suggestion in @city",
      "ai_health_assistant"=>"AI Health Assistant",
      "ai_typing"=>"AI is typing...",
      "describe_health_issue"=>"Describe your health issue...",
      "into_chat_placeholder"=>"Hi, how are you? I am your AI health assistant.",
      "into_chat_placeholder_2"=>"Describe your health concern. Our AI will recommend the best available doctor in @city.",
      "no_doctors_found"=>"Sorry, no doctors were found matching your search. Please try adjusting your filters or choose a different department."

    ];

    private $doctor_app_default_lng_json = [

        "hello!" => "Hello",
        "doctor" => "Doctor",
        "rating_review_text" => "@rating (@count Review)",
        "OPD" => "OPD",
        "Video" => "Video",
        "Emergency" => "Emergency",
        "appointment" => "Appointment",
        "today" => "Today",
        "pending" => "Pending",
        "cancelled" => "Cancelled",
        "confirmed" => "Confirmed",
        "rejected" => "Rejected",
        "view_all_btn" => "View All >",
        "last_20_appointments" => "Last 20 appointments",
        "no_appointment_found" => "No appointment found",
        "name:" => "Name: ",
        "time:" => "Time: ",
        "Pending" => "Pending",
        "Confirmed" => "Confirmed",
        "Rejected" => "Rejected",
        "Completed" => "Completed",
        "Rescheduled" => "Rescheduled",
        "Cancelled" => "Cancelled",
        "Visited" => "Visited",
        "month_jan" => "JAN",
        "month_feb" => "FEB",
        "month_mar" => "MARCH",
        "month_apr" => "APRIL",
        "month_may" => "MAY",
        "month_jun" => "JUN",
        "month_jul" => "JULY",
        "month_aug" => "AUG",
        "month_sep" => "SEP",
        "month_oct" => "OCT",
        "month_nov" => "NOV",
        "month_dec" => "DEC",
        "Video Consultant" => "Video Consultant",
        "Video Call" => "Video Call",
        "doctor_value" => "Doctor @value",
        "fee_value" => "Fee @value",
        "enable" => "Enable",
        "disable" => "Disable",


        "prescription" => "Prescription",
        "search..." => "Search...",
        "prescription_id" => "Prescription ID #@id",
        "appointment_id" => "Appointment ID #@id",
        "delete" => "Delete",
        "delete_prescription_box_value" => "Are you sure want to delete prescription #@id",
        "yes" => "Yes",
        "no" => "No",
        "success" => "success",

        "contact_us" => "Contact Us",
        "address_value" => "Address: @value",
        "phone_value" => "Phone: @value",
        "whatsapp_value" => "Whatsapp: @value",
        "notification" => "Notification",


        "share" => "Share",
        "knock_knock" => "knock knock",
        "share_app_with_friends" => "Share app with friends",


        "about_us" => "About Us",
        "privacy_policy" => "Privacy Policy",
        "terms_condition" => "Terms & Condition",

        "logout" => "Logout",
        "app_version" => "Version @vnumber",
        "add_prescription" => "Add Prescription",

        "dear_patient_name" => "Dear @pName",
        "date" => "Date",
        "time" => "Time",
        "payment_status_value" => "Payment Status @status",
        "Unpaid" => "Unpaid",
        "Paid" => "Paid",
        "Partially Paid" => "Partially Paid",
        "download_invoice" => "Download Invoice",
        "Initiated" => "Initiated",
        "Approved" => "Approved",
        "Processing" => "Processing",
        "appointment_cancellation_request" => "Appointment Cancellation Request",
        "no_cancellation_generated_by_user_desc" => "No cancellation request generated by user. you can reject this appointment",
        "current_status_value" => "Current Status - @value",
        "click_here_to_cancel_this_appointment." => "Click here to cancel this appointment.",
        "canceled_appointment_status_desc" => "Appointment has been canceled, can't edit the status.",
        "cancellation_request_history" => "Cancellation Request History",
        "no_prescription_found" => "No Prescription Found!",
        "patient_files" => "Patient Files",
        "no_file_found" => "No FIle Found!",
        "click_the_patient_file" => "Click here to check the patient file",
        "current_appointment_status_is" => "Current appointment status is ",
        "either_mark_text" => "Can be marked if the appointment is either OPD or Emergency.",
        "can_marked_video_consultant" => "Can be marked if the appointment is Video Consultant.",
        "save" => "Save",
        "delete_prescription_id" => "Are you sure want to delete prescription #@id",
        "choose_dare_and_time" => "Choose Date And Time",
        "no_available_time_slots" => "Sorry, no available time slots were found for the selected date",
        "something_went_wrong" => "Something Went Wrong",
        "select_the_different_time" => "Select the different time",
        'choose_prescription_mode' => "Choose Prescription Mode ",
        "hand_written_mode" => "Hand Written Mode",
        "predefined_mode" => "Predefined Mode",

        "update_prescription" => "Update Prescription",
        "problem" => "Problem",
        "test" => "Test",
        "advice" => "Advice",
        "next_visit" => "Next visit",
        "next_visit_in_day" => "Next visit in Day",
        "food_allergies" => "Food Allergies",
        "tendency_to_bleed" => "Tendency to Bleed",
        "heart_disease" => "Heart Disease",
        "blood_pressure" => "Blood Pressure",
        "diabetic" => "Diabetic",
        "surgery" => "Surgery",
        "accident" => "Accident",
        "others" => "Others",
        "medical_history" => "Medical History",
        "current_medication" => "Current Medication",
        "female_pregnancy" => "Female Pregnancy",
        "breast_feeding" => "Breast Feeding",
        "pulse_rate" => "Pulse Rate",
        "temperature" => "Temperature",
        "add_medicine" => "Add Medicine",
        "medicine" => "Medicine",
        "dosage" => "Dosage",
        "dosage*" => "Dosage",
        "duration" => "Duration",
        "duration*" => "Duration*",
        "time*" => "Time*",
        "dose_interval" => "Dose Interval",
        "dose_interval*" => "Dose Interval",
        "notes" => "Notes",
        "add" => "Add",
        "select_medicine" => "Select Medicine",
        "medicine_name_already_exists" => "Medicine name already exists",
        "dosage_value" => "Dosage -@value",
        "duration_value" => "Duration - @value",
        "time_value" => "Time - @value",
        "dose_interval_value" => "Dose Interval - @value",
        "notes_value" => "Notes - @value",
        'are_you_sure_want_to_delete' => "Are you sure want to delete @value",
        "For 1 day" => "For 1 day",
        "For 2 days" => "For 2 days",
        "For 3 days" => "For 3 days",
        "For 7 days" => "For 7 days",
        "For 15 days" => "For 15 days",
        "For 1 month" => "For 1 month",
        "After Meal" => "After Meal",
        "Before Meal" => "Before Meal",
        "Once a day" => "Once a day",
        "Every Morning & Evening" => "Every Morning & Evening",
        "3 Times a day" => "3 Times a day",
        "4 Times a day" => "4 Times a day",
        "appointmentStatusChangeWarning" => "Once an appointment is marked as Rejected, its status cannot be changed. Additionally, if any payment has been made by the user, it can be refunded to their wallet.\nAre you sure want to update status from @currentStatus to @newStatus?",
        "appointmentStatusChangeWarning_2_value" => "Once an appointment is marked as Cancelled, its status cannot be changed. Additionally, if any payment has been made by the user, it can be refunded to their wallet.\nAre you sure want to update status from @currentStatus to @selectedAppointmentStatus",
        "appointmentStatusChangeWarning_3_value" => "Are you sure want to rescheduled appointment from @date - @timeslot  to @to_date - @to_time",
        "appointmentStatusChangeWarning_4_value" => "Are you sure want to update status from @currentStatus to @newStatus",
        "login" => "Login",
        "credentials_to_login" => "Enter Credentials to login",
        "email" => "Email",
        "enter_email_address" => "Enter email address",
        "valid_email_address" => "Enter a valid email address",
        "password" => "Password",
        "enter_password" => "Enter password",
        "submit" => "Submit",
        "not_been_assigned_role." => "The user has not been assigned a doctor role.",
        "write_prescription" => "Write Prescription",
        "page_value" => "Page @value_1/@value_2",
        "delete_page" => "Delete Page",
        "delete_the_current_page" => "Are you sure want to delete the current page @value",
        "confirmation" => "Confirmation",
        "upload_pages_prescription_desc" => "Are you sure you want to save and upload the pages to the patient's prescription?",
        "membership_since_date" => "Membership since @date",
        "invoice_id" => "Invoice Id #@id"



    ];


    // ADD LANGUAGE
    function addData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'code'  => 'required'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            $alreadyAdded = LanguagesModel::where("code", $request->code)->first();
            if ($alreadyAdded) {
                return Helpers::errorResponse("Language code already exists");
            }

            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");

            // Save language
            $dataModel = new LanguagesModel();
            $dataModel->title = $request->title;
            $dataModel->code = $request->code;
            $dataModel->direction = $request->direction ?? 'ltr';
            $dataModel->is_default = $request->is_default ?? 0;
            $dataModel->active = $request->active ?? 1;
            $dataModel->sort_order = $request->sort_order ?? 0;
            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;
            $dataModel->save();

            // Insert User App JSON
            $dataModeLn = new LanguagesFileModel();
            $dataModeLn->language_id = $dataModel->id;
            $dataModeLn->scope = 'user_app';
            $dataModeLn->json_data = json_encode($this->user_app_default_lng_json, JSON_UNESCAPED_UNICODE);
            $dataModeLn->created_at = $timeStamp;
            $dataModeLn->updated_at = $timeStamp;
            $dataModeLn->save();
            if (!$dataModeLn) {
                return Helpers::errorResponse("Failed to insert user app json");
            }


            // Insert Doctor App JSON
            $dataModeLn = new LanguagesFileModel();
            $dataModeLn->language_id = $dataModel->id;
            $dataModeLn->scope = 'doctor_app';
            $dataModeLn->json_data = json_encode($this->doctor_app_default_lng_json, JSON_UNESCAPED_UNICODE);
            $dataModeLn->created_at = $timeStamp;
            $dataModeLn->updated_at = $timeStamp;
            $dataModeLn->save();
            if (!$dataModeLn) {
                return Helpers::errorResponse("Failed to insert doctor app json");
            }
            // Insert Doctor App JSON
            $dataModeLn = new LanguagesFileModel();
            $dataModeLn->language_id = $dataModel->id;
            $dataModeLn->scope = 'web';
            $dataModeLn->json_data = json_encode($this->web_default_lng_json, JSON_UNESCAPED_UNICODE);
            $dataModeLn->created_at = $timeStamp;
            $dataModeLn->updated_at = $timeStamp;
            $dataModeLn->save();
            if (!$dataModeLn) {
                return Helpers::errorResponse("Failed to insert web json");
            }

            DB::commit();
            return Helpers::successWithIdResponse("successfully", $dataModel->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error " . $e->getMessage());
        }
    }

    // UPDATE LANGUAGE
    function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        try {


            $dataModel = LanguagesModel::where("id", $request->id)->first();
            if (!$dataModel)
                return Helpers::errorResponse("Language not found");
            if (isset($request->active)) {
                if ($request->active == 0) {
                    if ($dataModel->is_default == 1) {
                        return Helpers::errorResponse("you can not deactivate default language");
                    }
                    //check if any other language is active
                    $activeLanguage = LanguagesModel::where("active", 1)->where("id", "!=", $request->id)->first();
                    if (!$activeLanguage) {
                        return Helpers::errorResponse("you can not deactivate this language as no other language is active");
                    }
                }
            }

            DB::beginTransaction();

            if (isset($request->title)) $dataModel->title = $request->title;
            if (isset($request->code)) $dataModel->code = $request->code;
            if (isset($request->direction)) $dataModel->direction = $request->direction;
            if (isset($request->is_default)) {
                if ($request->is_default == 1) {
                    LanguagesModel::where("is_default", 1)->update(['is_default' => 0]);
                }
                $dataModel->is_default = $request->is_default;
            }
            $dataModel->active = isset($request->is_default) && $request->is_default == 1 ? 1 : $request->active ?? $dataModel->active;
            if (isset($request->sort_order)) $dataModel->sort_order = $request->sort_order;

            $dataModel->updated_at = date("Y-m-d H:i:s");
            $dataModel->save();

            DB::commit();
            return Helpers::successResponse("successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error " . $e->getMessage());
        }
    }

    // GET LANGUAGES
    public function getData(Request $request)
    {
        $query = DB::table("languages")->orderBy("sort_order", "ASC");

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('active')) {
            $query->where('active', $request->active);
        }

        $total = $query->count();

        if ($request->filled(['start', 'end'])) {
            $query->skip($request->start)->take($request->end - $request->start);
        }

        return response()->json([
            "response" => 200,
            "total_record" => $total,
            "data" => $query->get()
        ], 200);
    }

    // GET BY ID
    function getDataById($id)
    {
        return response([
            "response" => 200,
            "data" => LanguagesModel::find($id)
        ], 200);
    }

    // DELETE
    function deleteData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            //check if defatult value then canot delet e
            $dataModel = LanguagesModel::where("id", $request->id)->first();
            if ($dataModel->is_default == 1) {
                return Helpers::errorResponse("Default language cannot be deleted");
            }

            LanguagesModel::where("id", $request->id)->delete();
            //if table file all delete then add the default language english 
            $count = LanguagesModel::count();
            if ($count == 0) {
                $dataModel = new LanguagesModel();
                $dataModel->title = "English";
                $dataModel->code = "en";
                $dataModel->direction = "ltr";
                $dataModel->is_default = 1;
                $dataModel->active = 1;
                $dataModel->sort_order = 1;
                $dataModel->created_at = date("Y-m-d H:i:s");
                $dataModel->updated_at = date("Y-m-d H:i:s");
                $dataModel->save();
            }

            return Helpers::successResponse("successfully Deleted");
        } catch (\Exception $e) {
            return Helpers::errorResponse("This language is in use and cannot be deleted.");
        }
    }
}
