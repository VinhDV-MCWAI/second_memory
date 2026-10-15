<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files of the file manager attached to an evidence (LEDGER-01). A file in the trash keeps its link,
 * so restoring it brings the attachment back; deleting it for good removes the link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_media', function (Blueprint $table) {
            $table->foreignId('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media_mgmt')->cascadeOnDelete();
            $table->smallInteger('position')->default(0)->comment('Order in the evidence, from 0');
            $table->primary(['evidence_id', 'media_id']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_media');
    }
};
