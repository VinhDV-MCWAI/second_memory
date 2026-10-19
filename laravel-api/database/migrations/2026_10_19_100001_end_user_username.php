<?php

declare(strict_types=1);

use App\Constants\EndUserConst;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * End users sign in with a username instead of an email (ADR-0021). Existing accounts get the part
 * of their email before "@", reduced to the allowed characters, with a number when taken.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('end_user', function (Blueprint $table) {
            $table->string('username', EndUserConst::USERNAME_MAX)->nullable();
        });

        $taken = [];
        foreach (DB::table('end_user')->orderBy('id')->get(['id', 'email']) as $row) {
            $username = $this->freeUsername($this->usernameFrom((string) $row->email), $taken);
            $taken[$username] = true;
            DB::table('end_user')->where('id', $row->id)->update(['username' => $username]);
        }

        DB::statement('DROP INDEX end_user_email_lower_unique');
        Schema::table('end_user', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->string('username', EndUserConst::USERNAME_MAX)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('end_user', function (Blueprint $table) {
            $table->string('email', 191)->nullable();
        });
        DB::statement("UPDATE end_user SET email = username || '@localhost'");
        Schema::table('end_user', function (Blueprint $table) {
            $table->string('email', 191)->nullable(false)->change();
            $table->dropColumn('username');
        });
        DB::statement('CREATE UNIQUE INDEX end_user_email_lower_unique ON end_user (lower(email))');
    }

    private function usernameFrom(string $email): string
    {
        $local = Str::lower(Str::before($email, '@'));
        $clean = trim((string) preg_replace('/[^a-z0-9._-]+/', '-', $local), '-');

        return Str::limit(str_pad($clean, EndUserConst::USERNAME_MIN, '0'), EndUserConst::USERNAME_MAX - 4, '');
    }

    /**
     * @param  array<string, true>  $taken
     */
    private function freeUsername(string $base, array $taken): string
    {
        $candidate = $base;
        for ($suffix = 2; isset($taken[$candidate]); $suffix++) {
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }
};
