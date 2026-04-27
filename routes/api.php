<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\SpecializationController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\TimeSlotsController;
use App\Http\Controllers\Api\V1\TimeSlotsVideoController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\ProductCatController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AllowPincodeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\LabTestController;
use App\Http\Controllers\Api\V1\LabReqController;
use App\Http\Controllers\Api\V1\DoctorsReviewController;
use App\Http\Controllers\Api\V1\AppointmentCancellationRedController;
use App\Http\Controllers\Api\V1\AppointmentRescheduleReqController;
use App\Http\Controllers\Api\V1\PrescribeMedicinesController;
use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Controllers\Api\V1\AllTransactionController;
use App\Http\Controllers\Api\V1\AppointmentInvoiceController;
use App\Http\Controllers\Api\V1\AppointmentPaymentController;
use App\Http\Controllers\Api\V1\RazorpayController;
use App\Http\Controllers\Api\V1\ZoomVideoCallController;
use App\Http\Controllers\Api\V1\FamilyMembersController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\RoleAssignController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RolePermissionController;
use App\Http\Controllers\Api\V1\LoginScreenController;
use App\Http\Controllers\Api\V1\WebPageController;
use App\Http\Controllers\Api\V1\SocialMediaController;
use App\Http\Controllers\Api\V1\ConfigurationsController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\SendNotificationController;
use App\Http\Controllers\Api\V1\StorageLinkController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\Api\V1\DoctorNotificationController;
use App\Http\Controllers\Api\V1\VitalsMeasurementsController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\CouponUseController;
use App\Http\Controllers\Api\V1\AppointmentCheckinController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\PaymentGatewayController;
use App\Http\Controllers\Api\V1\PatientFilesController;
use App\Http\Controllers\Api\V1\AdminNotificationController;
use App\Http\Controllers\Api\V1\AppointmentStatusLogController;
use App\Http\Controllers\Api\V1\SmtpController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\StatesController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\ClinicController;
use App\Http\Controllers\Api\V1\ClinicDoctorController;
use App\Http\Controllers\Api\V1\ClinicImagesController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\PatientClinicController;
use App\Http\Controllers\Api\V1\ReferralRequestsController;
use App\Http\Controllers\Api\V1\ContactFormInboxController;
use App\Http\Controllers\Api\V1\BlogPostCatController;
use App\Http\Controllers\Api\V1\BlogPostController;
use App\Http\Controllers\Api\V1\BlogAuthorController;
use App\Http\Controllers\Api\V1\PathologistController;
use App\Http\Controllers\Api\V1\PathologyImageController;
use App\Http\Controllers\Api\V1\PathologyTestCategoryController;
use App\Http\Controllers\Api\V1\PathologyTestController;
use App\Http\Controllers\Api\V1\UploadImageController;
use App\Http\Controllers\Api\V1\LabTestCartController;
use App\Http\Controllers\Api\V1\LabBookingController;
use App\Http\Controllers\Api\V1\PathologyTestSubController;
use App\Http\Controllers\Api\V1\PathologySubTestController;
use App\Http\Controllers\Api\V1\TaxesController;
use App\Http\Controllers\Api\V1\ServiceChargesController;
use App\Http\Controllers\Api\V1\ServicesController;
use App\Http\Controllers\Api\V1\PreOrderController;
use App\Http\Controllers\Api\V1\LabReviewController;
use App\Http\Controllers\Api\V1\LabAppointmentCancellationReqController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\LanguagesController;
use App\Http\Controllers\Api\V1\LanguagesFileController;
use App\Http\Controllers\Api\V1\payments\PaymentInitiateController;
use App\Http\Controllers\Api\V1\AiChatController;
use App\Http\Controllers\Api\Bancard\Card\BancardCardPaymentController;
use App\Http\Controllers\Api\Bancard\Card\BancardCardWebhookController;
use App\Http\Controllers\Api\V1\AgoraVideoController;
use App\Http\Controllers\Api\Bancard\Card\BancardCardLabBookingPaymentController;

use App\Http\Controllers\Api\V1\DoctorWeb\DoctorWebAuthController;
use App\Http\Controllers\Api\V1\DoctorWeb\DoctorWebDashboardController;
use App\Http\Controllers\Api\V1\DoctorWeb\DoctorWebAppointmentController;
use App\Http\Controllers\Api\V1\DoctorWeb\DoctorWebPatientFileController;
use App\Http\Controllers\Api\V1\DoctorWeb\DoctorWebReviewController;
use App\Http\Controllers\Api\V1\DoctorWeb\DoctorWebNotificationController;
use App\Http\Controllers\Api\V1\DoctorWeb\DoctorGoogleCalendarController;
use App\Http\Controllers\Api\V1\PaymentTypeController;
use App\Http\Controllers\Api\V1\ClinicDoctorBrowseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::prefix('v1')->group(function () {

    Route::get('get_payment_types', [PaymentTypeController::class, 'getData']);
    Route::get('get_city_clinics', [ClinicDoctorBrowseController::class, 'getCityClinics']);
    Route::get('get_clinic_doctors', [ClinicDoctorBrowseController::class, 'getClinicDoctors']);
    Route::get('get_city_clinics_with_doctors', [ClinicDoctorBrowseController::class, 'getCityClinicsWithDoctors']);

    Route::middleware(['auth:sanctum'])->prefix('doctor-web')->group(function () {
        Route::get('/google-calendar/connect-url', [DoctorGoogleCalendarController::class, 'getConnectUrl']);
        Route::get('/google-calendar/status', [DoctorGoogleCalendarController::class, 'status']);
        Route::post('/google-calendar/disconnect', [DoctorGoogleCalendarController::class, 'disconnect']);
    });

    Route::get('/doctor-web/google-calendar/callback', [DoctorGoogleCalendarController::class, 'callback']);

    Route::middleware('auth:sanctum')->post(
        'appointments/{id}/video/join-data',
        [AppointmentController::class, 'getVideoJoinData']
    );
    Route::get('/clinics/{clinicId}/doctors', [ClinicDoctorController::class, 'getDoctorsByClinic']);

    Route::get('/doctors/{doctorId}/clinics/{clinicId}/time-slots', [TimeSlotsController::class, 'getDoctorClinicSlots']);
    Route::get('/doctors/{doctorId}/clinics/{clinicId}/time-interval/{day}', [TimeSlotsController::class, 'getDoctorClinicTimeInterval']);

    Route::get('/doctors/{doctorId}/clinics/{clinicId}/video-time-slots', [TimeSlotsVideoController::class, 'getDoctorClinicVideoSlots']);
    Route::get('/doctors/{doctorId}/clinics/{clinicId}/video-time-interval/{day}', [TimeSlotsVideoController::class, 'getDoctorClinicVideoTimeInterval']);
});



Route::prefix('v1/doctor-web')->group(function () {
    Route::post('/login-google', [DoctorWebAuthController::class, 'loginGoogle']);
    Route::post('/login', [DoctorWebAuthController::class, 'login']);
});

Route::prefix('v1/doctor-web')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('/logout', [DoctorWebAuthController::class, 'logout']);
        Route::get('/me', [DoctorWebAuthController::class, 'me']);

        Route::get('/dashboard', [DoctorWebDashboardController::class, 'index']);

        Route::get('/appointments', [DoctorWebAppointmentController::class, 'index']);
        Route::get('/appointments/{id}', [DoctorWebAppointmentController::class, 'show']);
        Route::post('/appointments/{id}/confirm', [DoctorWebAppointmentController::class, 'confirm']);
        Route::post('/appointments/{id}/cancel', [DoctorWebAppointmentController::class, 'cancel']);
        Route::post('/appointments/{id}/complete', [DoctorWebAppointmentController::class, 'complete']);
        Route::post('/appointments/{id}/reschedule', [DoctorWebAppointmentController::class, 'reschedule']);
        Route::post('/appointments/{id}/video/join-data', [DoctorWebAppointmentController::class, 'videoJoinData']);

        Route::get('/patients/{patientId}/files', [DoctorWebPatientFileController::class, 'index']);

        Route::get('/reviews', [DoctorWebReviewController::class, 'index']);

        Route::get('/notifications', [DoctorWebNotificationController::class, 'index']);
        Route::post('/notifications/{id}/seen', [DoctorWebNotificationController::class, 'markSeen']);
    });


Route::post('v1/login_google_doctor', [LoginController::class, 'loginGoogleDoctor']);

Route::post('v1/login_google', [LoginController::class, 'loginGoogle']);

Route::prefix('v1/bancard/card')->group(function () {
    Route::post('lab-booking/start', [BancardCardLabBookingPaymentController::class, 'startLabBookingPayment']);
    Route::post('lab-booking/confirm', [BancardCardLabBookingPaymentController::class, 'confirmLabBookingPayment']);
    Route::post('lab-booking/cancel-and-rollback', [BancardCardLabBookingPaymentController::class, 'cancelAndRollbackLabBookingPayment']);
    Route::get('lab-booking/current/{labBooking}', [BancardCardLabBookingPaymentController::class, 'findCurrentByLabBooking']);
});


Route::post('v1/agora/video/join-data', [AgoraVideoController::class, 'getJoinData']);
Route::post('/bancard/card/appointment/start-payment', [BancardCardPaymentController::class, 'startAppointmentPayment']);
Route::get('/bancard/card/appointment/{appointment}/current-payment', [BancardCardPaymentController::class, 'findCurrentByAppointment']);
Route::post('/bancard/card/appointment/confirm-payment', [BancardCardPaymentController::class, 'confirmAppointmentPayment']);
Route::post('/bancard/card/appointment/cancel-payment', [BancardCardPaymentController::class, 'cancelAndRollbackAppointmentPayment']);
Route::post('/bancard/card/webhook', [BancardCardWebhookController::class, 'handle']);


Route::prefix('v1/bancard/card')->group(function () {
    Route::post('appointment/start', [BancardCardPaymentController::class, 'startAppointmentPayment']);
    Route::get('appointment/current/{appointment}', [BancardCardPaymentController::class, 'findCurrentByAppointment']);
    Route::post('appointment/confirm', [BancardCardPaymentController::class, 'confirmAppointmentPayment']);
    Route::post('appointment/cancel-and-rollback', [BancardCardPaymentController::class, 'cancelAndRollbackAppointmentPayment']);
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::group(['prefix' => 'v1', 'namespace' => 'api\v1', 'middleware' => 'auth:sanctum'], function () {

    Route::post("payment_initiate", [PaymentInitiateController::class, 'initiate']);

    
    //Users

    Route::post("update_password", [UserController::class, 'updatePassword']);
    Route::post("remove_user_image", [UserController::class, 'removeImage']);
    Route::post("update_user", [UserController::class, 'updateDetails']);
    Route::post("user_soft_delete", [UserController::class, 'softDeleted']);
    Route::post("user_delete", [UserController::class, 'deleteData']);

    //Login
    
    //Route::post("logout", [LoginController::class, 'logout']);
    Route::middleware('auth:sanctum')->post('logout', [LoginController::class, 'logout']);



    //Role
    Route::post("add_role", [RoleController::class, 'addData']);
    Route::post("update_role", [RoleController::class, 'updateData']);
    Route::post("delete_role", [RoleController::class, 'deleteData']);

    //RoleAssign
    Route::post("assign_role", [RoleAssignController::class, 'addData']);
    Route::post("de_assign_role", [RoleAssignController::class, 'deleteData']);

    //RolePermission
    Route::post("assign_permission_to_tole", [RolePermissionController::class, 'addData']);
    // Route::post("de_assign_role",[RolePermissionController::class,'deleteData']);

    //Department
    Route::post("add_department", [DepartmentController::class, 'addData']);
    Route::post("udpate_department", [DepartmentController::class, 'updateData']);
    Route::post("remove_department_image", [DepartmentController::class, 'removeImage']);
    Route::post("delete_department", [DepartmentController::class, 'deleteData']);

    // PathologyTestCategoryController
    Route::post("add_path_cat", [PathologyTestCategoryController::class, 'addData']);
    Route::post("udpate_path_cat", [PathologyTestCategoryController::class, 'updateData']);
    Route::post("remove_path_cat_image", [PathologyTestCategoryController::class, 'removeImage']);
    Route::post("delete_path_cat", [PathologyTestCategoryController::class, 'deleteData']);


    //PathologyTestController
    Route::post("add_path_test", [PathologyTestController::class, 'addData']);
    Route::post("udpate_path_test", [PathologyTestController::class, 'updateData']);
    Route::post("remove_path_test_image", [PathologyTestController::class, 'removeImage']);
    Route::post("delete_path_test", [PathologyTestController::class, 'deleteData']);


    //Specialization
    Route::post("add_specialization", [SpecializationController::class, 'addData']);
    Route::post("update_specialization", [SpecializationController::class, 'updateData']);
    Route::post("delete_specialization", [SpecializationController::class, 'deleteData']);

  




    //Doctors
    Route::post("add_doctor", [DoctorController::class, 'addData']);
    Route::post("remove_doctor_image", [DoctorController::class, 'removeImage']);
    Route::post("update_doctor", [DoctorController::class, 'updateData']);
    Route::post("delete_doctor", [DoctorController::class, 'deleteData']);
    Route::post('update_doctor_clinic_status', [DoctorController::class, 'updateDoctorClinicStatus']);

    //Doctors Review 
    Route::post("add_doctor_review", [DoctorsReviewController::class, 'addData']);


    //Patients
    Route::post("add_patient", [PatientController::class, 'addData']);
    Route::post("update_patient", [PatientController::class, 'updateData']);
    Route::post("remove_patient_image", [PatientController::class, 'removeImage']);
    Route::post("delete_patient", [PatientController::class, 'deleteData']);
    Route::post("link_mrn_lab_clinic_patient", [PatientController::class, 'linkMRNLabAndClinicPatient']);



    //TimeSlots
    Route::post("add_timeslots", [TimeSlotsController::class, 'addData']);
    Route::post("delete_timeslots", [TimeSlotsController::class, 'deleteData']);



    //Video TimeSlots
    Route::post("add_video_timeslots", [TimeSlotsVideoController::class, 'addData']);
    Route::post("delete_video_timeslots", [TimeSlotsVideoController::class, 'deleteData']);


    //Appointments
    Route::post("add_appointment", [AppointmentController::class, 'addData']);
    Route::post('add_first_appointment', [AppointmentController::class, 'addDataFirstAppointment']);
    Route::post("update_appointment_status", [AppointmentController::class, 'updateStatus']);
    Route::post("appointment_rescheduled", [AppointmentController::class, 'appointmentResch']);
    Route::post("user_appointment_reschedule", [AppointmentController::class, 'userAppointmentReschedule']);
    Route::post("update_appointment_to_paid", [AppointmentController::class, 'updateStatusToPaid']);


    //AppointmentStatusLogController
    Route::get("get_appointment_status_log", [AppointmentStatusLogController::class, 'getData']);
    Route::get("get_appointment_status_log/{id}", [AppointmentStatusLogController::class, 'getDataById']);



    //Appointment Cancellation
    Route::post("appointment_cancellation", [AppointmentCancellationRedController::class, 'addData']);
    Route::post("delete_appointment_cancellation", [AppointmentCancellationRedController::class, 'deleteDataByUser']);
    Route::post("delete_appointment_cancellation_by_admin", [AppointmentCancellationRedController::class, 'deleteData']);
    Route::post("appointment_cancellation_and_refund", [AppointmentCancellationRedController::class, 'cancleAndRefund']);
    Route::post("appointment_reject_and_refund", [AppointmentCancellationRedController::class, 'RejectAndRefund']);

    //Appointment Reschedule Request
    Route::post("appointment_reschedule_request", [AppointmentRescheduleReqController::class, 'addRequest']);
    Route::post("appointment_reschedule_request_approve", [AppointmentRescheduleReqController::class, 'approve']);
    Route::post("appointment_reschedule_request_reject", [AppointmentRescheduleReqController::class, 'reject']);
    Route::post("delete_appointment_reschedule_request", [AppointmentRescheduleReqController::class, 'deleteByUser']);
    Route::get("get_appointment_reschedule_requests/{id}", [AppointmentRescheduleReqController::class, 'getDataByAppointmentId']);
    Route::get("get_initiated_reschedule_requests", [AppointmentRescheduleReqController::class, 'getInitiatedList']);



    //Labt Cancellation
    Route::post("lab_booking_cancellation", [LabAppointmentCancellationReqController::class, 'addData']);
    Route::post("delete_lab_booking_cancellation", [LabAppointmentCancellationReqController::class, 'deleteDataByUser']);
    Route::post("delete_lab_booking_cancellation_by_admin", [LabAppointmentCancellationReqController::class, 'deleteData']);
    Route::post("lab_booking_cancellation_and_refund", [LabAppointmentCancellationReqController::class, 'cancleAndRefund']);
    Route::post("lab_booking_reject_and_refund", [LabAppointmentCancellationReqController::class, 'RejectAndRefundLabBooking']);

    //Prescribe Medicines
    Route::post("add_prescribe_medicines", [PrescribeMedicinesController::class, 'addData']);
    Route::post("update_prescribe_medicines", [PrescribeMedicinesController::class, 'updateData']);
    Route::post("delete_prescribe_medicines", [PrescribeMedicinesController::class, 'deleteData']);
    //Prescription
    Route::post("add_prescription", [PrescriptionController::class, 'addData']);
    Route::post("update_prescription", [PrescriptionController::class, 'updateData']);
    Route::post("delete_prescription", [PrescriptionController::class, 'deleteData']);
    Route::post("upload_prescription", [PrescriptionController::class, 'uploadPdfAndJson']);
 
    //AllTransaction
    Route::post("add_wallet_money", [AllTransactionController::class, 'updateWalletMoneyData']);

    //ZoomVideoCall
    Route::post("create_zoom_meeting", [ZoomVideoCallController::class, 'createMeeting']);

    //Patient
    Route::post("add_family_member", [FamilyMembersController::class, 'addData']);
    Route::post("delete_family_member", [FamilyMembersController::class, 'deleteData']);
    Route::post("update_family_member", [FamilyMembersController::class, 'updateData']);

    //LoginScreen
    Route::post("add_login_screen_image", [LoginScreenController::class, 'addData']);
    Route::post("update_login_screen_image", [LoginScreenController::class, 'updateData']);
    Route::post("delete_login_screen_image", [LoginScreenController::class, 'deleteData']);

    //BannerController
    Route::post("add_banner", [BannerController::class, 'addData']);
    Route::post("update_banner", [BannerController::class, 'updateData']);
    Route::post("delete_banner", [BannerController::class, 'deleteData']);

    //Testimonial
    Route::post("add_testimonial", [TestimonialController::class, 'addData']);
    Route::post("update_testimonial", [TestimonialController::class, 'updateData']);
    Route::post("delete_testimonial", [TestimonialController::class, 'deleteData']);
    Route::post('remove_testimonia_image', [TestimonialController::class, 'removeImage']);

    //SocialMedia
    Route::post('add_social_media', [SocialMediaController::class, 'addData']);
    Route::post('delete_social_media', [SocialMediaController::class, 'deleteData']);
    Route::post('update_social_media', [SocialMediaController::class, 'updateData']);
    Route::post('remove_social_media_image', [SocialMediaController::class, 'removeImage']);



    //WebPage
    Route::post("update_web_page", [WebPageController::class, 'updateData']);

    //Configurations
    Route::post("update_configurations", [ConfigurationsController::class, 'updateData']);
    Route::post("remove_configurations_image", [ConfigurationsController::class, 'removeImage']);

    //SendNotification
    Route::post("sent_notificaiton_to_token", [SendNotificationController::class, 'sendReqFirebaseNotificationToToken']);
    Route::post("sent_notificaiton_to_topic", [SendNotificationController::class, 'sendReqFirebaseNotificationToTopic']);
    Route::post("subscribe_to_topic", [SendNotificationController::class, 'subscribeToTopic']);



    //UserNotificationController
    Route::post("add_user_notification", [UserNotificationController::class, 'addData']);
    Route::post("delete_notification", [UserNotificationController::class, 'deleteData']);

    //DoctorNotification
    Route::post("delete_doctor_notification", [DoctorNotificationController::class, 'deleteData']);

    //VitalsMeasurementsController
    Route::post("add_vitals", [VitalsMeasurementsController::class, 'addData']);
    Route::post("delete_vitals", [VitalsMeasurementsController::class, 'deleteData']);
    Route::post("update_vitals", [VitalsMeasurementsController::class, 'updateData']);

    //Coupon
    Route::post("add_coupon", [CouponController::class, 'addData']);
    Route::post("update_coupon", [CouponController::class, 'updateDetails']);
    Route::post("delete_coupon", [CouponController::class, 'deleteData']);
    Route::post("get_validate", [CouponController::class, 'getValidate']);
    Route::post("get_validate_lab", [CouponController::class, 'getValidateLab']);

    //CouponUse
    Route::post("delete_coupon_use", [CouponUseController::class, 'deleteData']);

    //AppointmentCheckIn
    Route::post("add_appointment_checkin", [AppointmentCheckinController::class, 'addData']);
    Route::post("delete_appointment_checkin", [AppointmentCheckinController::class, 'deleteData']);
    Route::post("update_appointment_checkin", [AppointmentCheckinController::class, 'updateDetails']);


    //PaymentGateway 
    Route::post("update_payment_gateway", [PaymentGatewayController::class, 'updateData']);

    //PatientFiles
    Route::post("add_patient_file", [PatientFilesController::class, 'addData']);
    Route::post("update_patient_file", [PatientFilesController::class, 'updateData']);
    Route::post("delete_patient_file", [PatientFilesController::class, 'deleteData']);


    //CountryController
    Route::post("add_country", [CountryController::class, 'addData']);
    Route::post("update_country", [CountryController::class, 'updateData']);
    Route::post("delete_country", [CountryController::class, 'deleteData']);

    //StatesController
    Route::post("add_state", [StatesController::class, 'addData']);
    Route::post("update_state", [StatesController::class, 'updateData']);
    Route::post("delete_state", [StatesController::class, 'deleteData']);

    //CityController
    Route::post("add_city", [CityController::class, 'addData']);
    Route::post("update_city", [CityController::class, 'updateData']);
    Route::post("delete_city", [CityController::class, 'deleteData']);


    //ClinicController
    Route::post("add_clinic", [ClinicController::class, 'addData']);
    Route::post("update_clinic", [ClinicController::class, 'updateData']);
    Route::post("remove_clinic_image", [ClinicController::class, 'removeImage']);
    Route::post("delete_clinic", [ClinicController::class, 'deleteData']);

    //ClinicDoctorController
    Route::post("add_clinic_doctor", [ClinicDoctorController::class, 'addData']);
    Route::post("delete_clinic_doctor", [ClinicDoctorController::class, 'deleteData']);

    //ClinicImagesController
    Route::post("add_clinic_image", [ClinicImagesController::class, 'addData']);
    Route::post("delete_clinic_image", [ClinicImagesController::class, 'deleteData']);

    Route::post("add_pathology_image", [PathologyImageController::class, 'addData']);
    Route::post("delete_pathology_image", [PathologyImageController::class, 'deleteData']);

    //PatientClinicController
    Route::post("add_patient_clinic", [PatientClinicController::class, 'addData']);
    Route::post("delete_patient_clinic", [PatientClinicController::class, 'deleteData']);

    //ReferralRequestsController
    Route::post("add_referral_clinic", [ReferralRequestsController::class, 'addData']);
    Route::post("update_referral_clinic", [ReferralRequestsController::class, 'updateStatus']);
    Route::post("delete_referral_clinic", [ReferralRequestsController::class, 'deleteRequest']);

    Route::post("delete_contact_us_form_data", [ContactFormInboxController::class, 'deleteData']);

    //BlogPostCatController

    Route::post("add_blog_cat", [BlogPostCatController::class, 'addData']);
    Route::post("update_blog_cat", [BlogPostCatController::class, 'updateData']);
    Route::post("delete_blog_cat", [BlogPostCatController::class, 'deleteData']);

    //BlogPostController
    Route::post("add_blog_post", [BlogPostController::class, 'addData']);
    Route::post("update_blog_post", [BlogPostController::class, 'updateData']);
    Route::post("delete_blog_post", [BlogPostController::class, 'deleteData']);
    Route::post("remove_blog_post", [BlogPostController::class, 'removeImage']);
    Route::post("blog_post_mark_published", [BlogPostController::class, 'updateDataToPublish']);


    //BlogAuthorController
    Route::post("add_blog_post_author", [BlogAuthorController::class, 'addData']);
    Route::post("update_blog_post_author", [BlogAuthorController::class, 'updateData']);
    Route::post("delete_blog_post_author", [BlogAuthorController::class, 'deleteData']);

    //PathologistController
    Route::post("add_pathologist", [PathologistController::class, 'addData']);
    Route::post("update_pathologist", [PathologistController::class, 'updateData']);
    Route::post("remove_pathologist_image", [PathologistController::class, 'removeImage']);
    Route::post("delete_pathologist", [PathologistController::class, 'deleteData']);

    Route::post("add_blog_content_image", [UploadImageController::class, 'uploadBlogImage']); //
    Route::post("delete_blog_content_image", [UploadImageController::class, 'removeImage']); //

    Route::post("add_lab_cart", [LabTestCartController::class, 'addData']); //
    Route::post("delete_lab_cart", [LabTestCartController::class, 'deleteData']); //
    Route::post("delete_and_add_lab_test", [LabTestCartController::class, 'deleteAndAddLabTest']); //
    Route::post("delete_and_add_lab_test_for_web", [LabTestCartController::class, 'deleteAndAddLabTestForWeb']); //


    Route::post("update_lab_booking_status", [LabBookingController::class, 'updateStatus']); //
    Route::post("update_lab_booking_to_paid", [LabBookingController::class, 'updateStatusToPaid']);
    Route::post("add_lab_booking", [LabBookingController::class, 'addData']); //
    Route::post("lab_booking_rescheduled", [LabBookingController::class, 'appointmentResch']); //
      
    Route::post("update_payment", [WebhookController::class, 'updatePayment']);




    // PathologyTestSubCategoryController
    Route::post("add_path_sub_cat", [PathologyTestSubController::class, 'addData']);
    Route::post("update_path_sub_cat", [PathologyTestSubController::class, 'updateData']);
    Route::post("remove_path_sub_cat_image", [PathologyTestSubController::class, 'removeImage']);
    Route::post("delete_path_sub_cat", [PathologyTestSubController::class, 'deleteData']);

    // Subtest
    Route::post('add_path_sub_test', [PathologySubTestController::class, 'addData']);
    Route::post('update_path_sub_test', [PathologySubTestController::class, 'updateData']);

    Route::post('delete_path_sub_test', [PathologySubTestController::class, 'deleteData']);


    //TaxesController
    Route::post("add_tax", [TaxesController::class, 'addData']);
    Route::post("update_tax", [TaxesController::class, 'updateData']);
    Route::post("delete_tax", [TaxesController::class, 'deleteData']);

    //ServiceChargesController
    Route::post("add_service_charge", [ServiceChargesController::class, 'addData']);
    Route::post("update_service_charge", [ServiceChargesController::class, 'updateData']);
    Route::post("delete_service_charge", [ServiceChargesController::class, 'deleteData']);

    //ServicesController

    Route::post("add_services", [ServicesController::class, 'addData']);
    Route::post("update_services", [ServicesController::class, 'updateData']);
    Route::post("delete_services", [ServicesController::class, 'deleteData']);

    //PreOrderController
    Route::post("add_pre_order", [PreOrderController::class, 'addData']);

    //LabReviewController
    Route::post("add_lab_review", [LabReviewController::class, 'addData']);

    //ServiceCategoryController
    Route::post("add_service_category", [ServiceCategoryController::class, 'addData']);
    Route::post("update_service_category", [ServiceCategoryController::class, 'updateData']);
    Route::post("delete_service_category", [ServiceCategoryController::class, 'deleteData']);


    Route::post("add_invoice", [AppointmentInvoiceController::class, 'addData']);
    Route::post("mark_paid_invoice", [AppointmentInvoiceController::class, 'markPaid']);
    Route::post("delete_invoice", [AppointmentInvoiceController::class, 'deleteData']);
    // imporsonate
    Route::post("impersonate_user", [LoginController::class, 'impersonateUser']);

    //LanguagesController
    Route::post("add_language", [LanguagesController::class, 'addData']);
    Route::post("update_language", [LanguagesController::class, 'updateData']);
    Route::post("delete_language", [LanguagesController::class, 'deleteData']);
    Route::post("update_language_translations", [LanguagesFileController::class, 'updateData']);





    
});

Route::group(['prefix' => 'v1', 'namespace' => 'api\v1', 'middleware' => 'api.key'], function () {




    Route::post("login", [LoginController::class, 'login']);
    Route::post("login_phone", [LoginController::class, 'loginMobile']);
    Route::post("re_login_phone", [LoginController::class, 'ReLoginMobile']);


    //Specialization
    Route::get("get_specialization", [SpecializationController::class, 'getData']);
    Route::get("get_specialization/{id}", [SpecializationController::class, 'getDataById']);

    //Doctors
    Route::get("get_doctor", [DoctorController::class, 'getData']);
    Route::get("get_doctor/{id}", [DoctorController::class, 'getDataById']);


    //Department
    Route::get("get_department", [DepartmentController::class, 'getData']);
    Route::get("get_department_active", [DepartmentController::class, 'getDataActive']);
    Route::get("get_department/{id}", [DepartmentController::class, 'getDataById']);


    //PathologyTestCategoryController

    Route::get("get_path_cat", [PathologyTestCategoryController::class, 'getData']);
    Route::get("get_path_cat/{id}", [PathologyTestCategoryController::class, 'getDataById']);

    //PathologyTestController
    Route::get("get_path_test", [PathologyTestController::class, 'getData']);
    Route::get("get_path_test/{id}", [PathologyTestController::class, 'getDataById']);




    //Doctors Review 
    Route::get("get_all_doctor_review", [DoctorsReviewController::class, 'getData']);
    



    //Patients
    Route::get("get_patients", [PatientController::class, 'getData']);
    Route::get("get_patients/{id}", [PatientController::class, 'getDataById']);
    Route::get("get_patient_by_mrn/{mrn}", [PatientController::class, 'getDataByMrn']);

    //Role
    Route::get("get_roles", [RoleController::class, 'getData']);
    Route::get("get_roles/{id}", [RoleController::class, 'getDataById']);


    //RoleAssign
    Route::get("get_assign_roles", [RoleAssignController::class, 'getData']);
    Route::get("get_assign_roles/{id}", [RoleAssignController::class, 'getDataById']);

    //Permission
    Route::get("get_permisssion", [PermissionController::class, 'getData']);
    Route::get("get_permisssion/{id}", [PermissionController::class, 'getDataById']);

    //RolePermission
    Route::get("get_role_permisssion", [RolePermissionController::class, 'getData']);
    Route::get("get_role_permisssion/{id}", [RolePermissionController::class, 'getDataById']);
    Route::get("get_role_permisssion/role/{id}", [RolePermissionController::class, 'getDataByRoleId']);

    // Legacy time-slots routes removed 2026-04-26 — handlers no longer exist.
    // Use the nested resource pattern declared near the top of this file:
    //   /v1/doctors/{doctorId}/clinics/{clinicId}/(video-)time-slots
    //   /v1/doctors/{doctorId}/clinics/{clinicId}/(video-)time-interval/{day}

    //Appointment
    Route::get("get_appointments", [AppointmentController::class, 'getData']);
    Route::get("get_appointment/{id}", [AppointmentController::class, 'getDataById']);
    Route::get("get_booked_time_slots", [AppointmentController::class, 'getBookedTimeSlotsByDoctIdAndDateAndTpe']);


    //Appointment Cancellation
    Route::get("get_appointment_cancel_req/appointment/{id}", [AppointmentCancellationRedController::class, 'getDataByAppointmentId']);

    Route::get("get_lab_booking_cancel_req", [LabAppointmentCancellationReqController::class, 'getData']);



    //users
    Route::get("get_user/{id}", [UserController::class, 'getDataById']);
    Route::get("get_users", [UserController::class, 'getData']);
    Route::get("get_users_date", [UserController::class, 'getDataByDate']);
    Route::post("add_user", [UserController::class, 'addData']);
    Route::get("get_users/page", [UserController::class, 'getDataPeg']);


    //Prescribe Medicines
    Route::get("get_prescribe_medicines/{id}", [PrescribeMedicinesController::class, 'getDataById']);
    Route::get("get_prescribe_medicines", [PrescribeMedicinesController::class, 'getData']);

    // prescription
    Route::get("get_prescription", [PrescriptionController::class, 'getData']);

    Route::get("consultation_report/{id}", [PrescriptionController::class, 'generate_blank_prescriptionsPDF']);
    Route::get("get_prescription/{id}", [PrescriptionController::class, 'getDataById']);


    //AllTransaction
    Route::get("get_all_transaction", [AllTransactionController::class, 'getData']);
    Route::get("get_all_transaction/{id}", [AllTransactionController::class, 'getDataById']);


    //AppointmentInvoice
    Route::get("get_invoice", [AppointmentInvoiceController::class, 'getData']);
    Route::get("get_invoice/{id}", [AppointmentInvoiceController::class, 'getDataById']);
    Route::get("get_invoice/appointment/{id}", [AppointmentInvoiceController::class, 'getDataByAppId']);
    Route::get("get_invoice/lab_appointment/{id}", [AppointmentInvoiceController::class, 'getDataByLabAppId']);


    //AppointmentPaymentController
    Route::get("get_appointment_payment", [AppointmentPaymentController::class, 'getData']);
    Route::get("get_appointment_payment/{id}", [AppointmentPaymentController::class, 'getDataById']);


    //FamilyMembers

    Route::get("get_family_members", [FamilyMembersController::class, 'getData']);
    Route::get("get_family_members/user/{id}", [FamilyMembersController::class, 'getDataByUserId']);
    Route::get("get_family_members/{id}", [FamilyMembersController::class, 'getDataById']);
    Route::get("get_family_member/page", [FamilyMembersController::class, 'getDataPeg']);


    //Dashboard
    Route::get("get_dashboard_count", [DashboardController::class, 'getDataDashBoardCount']);
    Route::get("get_dashboard_count/doctor/{id}", [DashboardController::class, 'getDataDashBoardCountByDoctor']);
    Route::get("get_dashboard_count/clinic/{id}", [DashboardController::class, 'getDataDashBoardCountByClinic']);
    Route::get("get_dashboard_count/pathology/{id}", [DashboardController::class, 'getDataDashBoardCountByPathId']);

    //LoginScreenController
    Route::get("get_login_screen_images", [LoginScreenController::class, 'getData']);
    Route::get("get_login_screen_images/{id}", [LoginScreenController::class, 'getDataById']);


    //BannerController
    Route::get("get_banner", [BannerController::class, 'getData']);
    Route::get("get_banner/{id}", [BannerController::class, 'getDataById']);


    //SocialMedia
    Route::get("get_social_media", [SocialMediaController::class, 'getDataAllData']);

    //WebPage
    Route::get("get_web_pages", [WebPageController::class, 'getData']);
    Route::get("get_web_page/page/{id}", [WebPageController::class, 'getDataByPageId']);

    //Configurations
    Route::get("get_configurations", [ConfigurationsController::class, 'getData']);
    Route::get("get_configurations/{id}", [ConfigurationsController::class, 'getDataById']);
    Route::get("get_configurations/id_name/{id_name}", [ConfigurationsController::class, 'getDataByIdName']);
    Route::get("get_configurations/group_name/{group_name}", [ConfigurationsController::class, 'getDataByGroupName']);
    Route::get("get_configurations_all", [ConfigurationsController::class, 'getDataAll']);

    //Testimonial
    Route::get("get_testimonial", [TestimonialController::class, 'getData']);
    Route::get("get_lab_testimonial", [TestimonialController::class, 'getDataLab']);
    Route::get("get_testimonial/{id}", [TestimonialController::class, 'getDataById']);

    //StorageLink
    Route::get("storage_link", [StorageLinkController::class, 'createSymbolicLink']);

    //UserNotification
    Route::get("get_user_notification", [UserNotificationController::class, 'getData']);
    Route::get("get_user_notification_page", [UserNotificationController::class, 'getDataPeg']);
    Route::get("get_user_notification/{id}", [UserNotificationController::class, 'getDataById']);
    Route::get("get_user_notification/date/{uid}/{date}", [UserNotificationController::class, 'getDataByDate']);
    Route::get("users_notification_seen_status/{id}", [UserNotificationController::class, 'checkNotificaitonSeen']);

    //DoctorNotification
    Route::get("get_doctor_notification", [DoctorNotificationController::class, 'getData']);
    Route::get("get_doctor_notification_page", [DoctorNotificationController::class, 'getDataPeg']);
    Route::get("get_doctor_notification/{id}", [DoctorNotificationController::class, 'getDataById']);
    Route::get("doctor_notification_seen_status/{id}", [DoctorNotificationController::class, 'checkNotificaitonSeen']);
    Route::get("get_doctor_notification/doctor/{id}", [DoctorNotificationController::class, 'getDataByDoctorId']);


    //AdminNotification
    Route::get("get_admin_notification", [AdminNotificationController::class, 'getData']);
    Route::get("get_admin_notification_page", [AdminNotificationController::class, 'getDataPeg']);

    Route::get("get_admin_notification/{id}", [AdminNotificationController::class, 'getDataById']);


    //VitalsMeasurements
    Route::get("get_vitals", [VitalsMeasurementsController::class, 'getData']);
    Route::get("get_vitals/{id}", [VitalsMeasurementsController::class, 'getDataById']);
    Route::get("get_vitals/user/{id}", [VitalsMeasurementsController::class, 'getDataByUserId']);
    Route::get("get_vitals/family_member/{id}", [VitalsMeasurementsController::class, 'getDataByFamilyMemberId']);
    Route::get("get_vitals_family_member_id_type", [VitalsMeasurementsController::class, 'getDataByFamilyMemberIdType']);

    //Coupon
    Route::get("get_coupon", [CouponController::class, 'getData']);
    Route::get("get_coupon/{id}", [CouponController::class, 'getDataById']);


    //CouponUse
    Route::get("get_coupon_use", [CouponUseController::class, 'getData']);
    Route::get("get_coupon_use/{id}", [CouponUseController::class, 'getDataById']);

    //AppointmentCheckIn
    Route::get("get_appointment_check_in", [AppointmentCheckinController::class, 'getData']);
    Route::get("get_appointment_check_in/{id}", [AppointmentCheckinController::class, 'getDataById']);


    



    //PaymentGateway 
    Route::get("get_payment_gateway", [PaymentGatewayController::class, 'getData']);
    Route::get("get_payment_gateway/{id}", [PaymentGatewayController::class, 'getDataById']);
    Route::get("get_payment_gateway_active", [PaymentGatewayController::class, 'getDataByActive']);


    //PatientFiles
    Route::get("get_patient_file", [PatientFilesController::class, 'getData']);
    Route::get("get_patient_file/{id}", [PatientFilesController::class, 'getDataById']);


    Route::post("forget_password", [SmtpController::class, 'sendForgetMail']);

    //CountryController
    Route::get("get_country", [CountryController::class, 'getData']);
    Route::get("get_country/{id}", [CountryController::class, 'getDataById']);

    //StatesController
    Route::get("get_states", [StatesController::class, 'getData']);
    Route::get("get_states/{id}", [StatesController::class, 'getDataById']);

    //CityController

    Route::get("get_city", [CityController::class, 'getData']);
    Route::get("get_city/{id}", [CityController::class, 'getDataById']);

    //ClinicController

    Route::get("get_clinic", [ClinicController::class, 'getData']);
    Route::get("get_clinic/{id}", [ClinicController::class, 'getDataById']);
    Route::get("get_clinic_page", [ClinicController::class, 'getData']);

    //PathologistController
    Route::get("get_pathologist", [PathologistController::class, 'getData']);
    Route::get("get_pathologist/{id}", [PathologistController::class, 'getDataById']);



    //
    Route::get("get_current_city", [CityController::class, 'getCityByLatLng']);


    //ClinicImagesController
    Route::get("get_clinic_images", [ClinicImagesController::class, 'getData']);
    Route::get("get_clinic_image/{id}", [ClinicImagesController::class, 'getDataById']);

    Route::get("get_pathology_images", [PathologyImageController::class, 'getData']);
    Route::get("get_pathology_images/{id}", [PathologyImageController::class, 'getDataById']);


    //PatientClinicController
    Route::get("get_patient_clinic", [PatientClinicController::class, 'getData']);
    Route::get("get_patient_clinic/{id}", [PatientClinicController::class, 'getDataById']);


    //ReferralRequestsController

    Route::get("get_referral_clinic", [ReferralRequestsController::class, 'getData']);
    Route::get("get_referral_clinic/{id}", [ReferralRequestsController::class, 'getDataById']);



    Route::post("add_contact_us_form_data", [ContactFormInboxController::class, 'addData']);
    Route::get("get_contact_us_form_data", [ContactFormInboxController::class, 'getData']);
    Route::get("get_contact_us_form_data/{id}", [ContactFormInboxController::class, 'getDataByid']);

    //BlogPostCatController
    Route::get("get_blog_cat", [BlogPostCatController::class, 'getData']);
    Route::get("get_blog_cat/{id}", [BlogPostCatController::class, 'getDataByid']);

    //BlogPostController
    Route::get("get_blog_post", [BlogPostController::class, 'getData']);
    Route::get("get_blog_post/{id}", [BlogPostController::class, 'getDataByid']);

    //BlogAuthorController

    Route::get("get_blog_post_author", [BlogAuthorController::class, 'getData']);
    Route::get("get_blog_post_author/{id}", [BlogAuthorController::class, 'getDataByid']);


    Route::get("get_lab_cart", [LabTestCartController::class, 'getData']);

    Route::get("get_lab_booking", [LabBookingController::class, 'getData']);
    Route::get("get_lab_booking/{id}", [LabBookingController::class, 'getDataByid']);


    Route::get("get_path_sub_cat", [PathologyTestSubController::class, 'getData']);
    Route::get("get_path_sub_cat/{id}", [PathologyTestSubController::class, 'getDataById']);


    Route::get('get_path_sub_test', [PathologySubTestController::class, 'getData']);
    Route::get('get_path_sub_test/{id}', [PathologySubTestController::class, 'getDataById']);
    // Route::get('get_path_sub_test_by_test/{id}', [PathologySubTestController::class, 'getDataByTest']);

    //TaxesController
    Route::get("get_tax", [TaxesController::class, 'getData']);
    Route::get("get_tax/{id}", [TaxesController::class, 'getDataById']);

    //PathologySubTestController
    Route::get("get_service_charge", [ServiceChargesController::class, 'getData']);
    Route::get("get_service_charge/{id}", [ServiceChargesController::class, 'getDataById']);

    //ServicesController
    Route::get("get_service", [ServicesController::class, 'getData']);
    Route::get("get_service/{id}", [ServicesController::class, 'getDataById']);

    //ServiceCategoryController
    Route::get("get_service_category", [ServiceCategoryController::class, 'getData']);
    Route::get("get_service_category/{id}", [ServiceCategoryController::class, 'getDataById']);

    //  LabReviewController
    Route::get("get_all_lab_review", [LabReviewController::class, 'getData']);

    //LanguagesController
    Route::get("get_language", [LanguagesController::class, 'getData']);
    Route::get("get_language/{id}", [LanguagesController::class, 'getDataById']);
    Route::get("get_language_translations", [LanguagesFileController::class, 'getData']);
    Route::get("get_language_translations_by_scope", [LanguagesFileController::class, 'getDataByScope']);

        Route::post("ai_chat", [AiChatController::class, 'chat']);
});


Route::group(['prefix' => 'v1', 'namespace' => 'api\v1'], function () {

    Route::post("rz_webhook", [WebhookController::class, 'handleWebhook']);
    Route::post("stripe_webhook", [WebhookController::class, 'handleWebhookStripe']);
    Route::get("invoice/generatePDF/{id}", [AppointmentInvoiceController::class, 'generatePDF']);
    Route::get("invoice/generatePDFLab/{id}", [AppointmentInvoiceController::class, 'generatePDFForLab']);
    Route::get("prescription/generatePDF/{id}", [PrescriptionController::class, 'generatePDF']);
 
});
