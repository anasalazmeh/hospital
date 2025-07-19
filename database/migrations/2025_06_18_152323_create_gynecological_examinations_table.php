<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gynecological_examinations', function (Blueprint $table) {
            // المعرف الأساسي للسجل
            $table->id()->comment('رقم فريد يمثل كل فحص في الجدول');

            // رقم السجل في جدول النسائية والتوليد
            $table->foreignId('obstetrics_gynecology_id')->constrained('obstetrics_gynecology','id')->onDelete('cascade')
                ->comment('يربط هذا الفحص بالسجل الرئيسي للمريضة في جدول النسائية والتوليد');

            // رقم الطبيب المسؤول
            $table->foreignId('doctor_id')->constrained('user_accounts','id')->onDelete('cascade')
                ->comment('رقم الطبيب أو الممرضة الذي أجرى الفحص');

            // نوع الفحص
            $table->enum('examination_type', ['prenatal', 'postnatal'])
                ->comment('نوع الفحص: prenatal (قبل الولادة) أو postnatal (بعد الولادة)');

            // أسبوع الحمل (للفحوصات قبل الولادة)
            $table->integer('pregnancy_week')->nullable()
                ->comment('أسبوع الحمل عند إجراء الفحص (من 1 إلى 40) - للحالات قبل الولادة فقط');

            // ========== نتائج الفحوصات ==========

            // معدل ضربات قلب الجنين
            $table->string('fetal_heart_rate')->nullable()
                ->comment('معدل ضربات قلب الجنين (مثال: 140 نبضة/دقيقة)');

            // انقباضات الرحم
            $table->string('uterine_contractions')->nullable()
                ->comment('قياس انقباضات الرحم (مثال: 3 انقباضات كل 10 دقائق)');

            // اتساع عنق الرحم
            $table->string('cervical_dilation')->nullable()
                ->comment('قياس فتحة عنق الرحم بالسنتيمتر (مثال: 4 سم)');

            // درجة حرارة الأم
            $table->decimal('temperature')->nullable()
                ->comment('درجة حرارة المريضة (مثال: 37.5 مئوية)');

            // ضغط الدم
            $table->string('blood_pressure')->nullable()
                ->comment('ضغط الدم (مثال: 120/80)');

            // حركة الجنين
            $table->integer('fetal_movement')->nullable()
                ->comment('تسجيل حركات الجنين (مثال: 10 حركات خلال ساعتين)');

            // ارتفاع قاع الرحم
            $table->string('fundal_height')->nullable()
                ->comment('قياس ارتفاع قاع الرحم بالسنتيمتر (مثال: 28 سم)');

            // نزيف ما بعد الولادة
            $table->string('postpartum_bleeding')->nullable()
                ->comment('كمية النزيف بعد الولادة (مثال: متوسط، غزير)');

            // فحص الموجات فوق الصوتية
            $table->string('ultrasound')->nullable()
                ->comment('نتائج فحص السونار (مثال: وضعية الجنين طبيعية)');

            // متابعة ما بعد الولادة
            $table->text('postpartum_monitoring')->nullable()
                ->comment('ملاحظات عن حالة الأم بعد الولادة');

            // الأدوية والجرعات
            $table->text('medication_doses')->nullable()
                ->comment('الأدوية الموصوفة مع جرعاتها (مثال: باراسيتامول 500mg كل 6 ساعات)');

            // إجراءات إضافية
            $table->text('additional_procedures')->nullable()
                ->comment('أي إجراءات طبية إضافية تمت خلال الفحص');

            // ملاحظات عامة
            $table->text('notes')->nullable()
                ->comment('أي ملاحظات إضافية يريد الطبيب تسجيلها');

            // تاريخ الإنشاء والتحديث
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gynecological_examinations');
    }
};
