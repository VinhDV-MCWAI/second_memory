<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Content\Article;
use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Development only (refuses production): sample content of the public site (ADR-0022) for the owner
 * to replace: a "Software process" group with one category per lifecycle step and two articles each,
 * a "Skills" group whose categories carry evidence, and a book of nested chapters.
 * Idempotent: categories and articles are matched by slug and never overwritten.
 */
class DevContentSeeder extends Seeder
{
    /** Groups keyed by slug: [name, description] */
    private const GROUPS = [
        'quy-trinh' => ['Quy trình làm phần mềm', 'Kinh nghiệm theo từng bước, từ lúc nhận yêu cầu tới khi vận hành.'],
        'ky-nang' => ['Kỹ năng', 'Công nghệ tôi dùng hằng ngày, kèm bằng chứng và bài viết kinh nghiệm.'],
    ];

    /**
     * Categories of the process group keyed by slug, in order: [name, description, articles as [slug, title, summary, body]].
     *
     * @var array<string, array{string, string, list<array{string, string, string, string}>}>
     */
    private const PROCESS = [
        'nhan-yeu-cau' => ['Nhận yêu cầu', 'Yêu cầu tới từ đâu, ghi lại thế nào, phân loại ra sao.', [
            ['ghi-lai-yeu-cau', 'Ghi lại mọi yêu cầu bằng chữ', 'Yêu cầu nhận qua lời nói phải thành chữ ở nơi còn lưu lại.', "## Vì sao\n\nNhận việc qua cuộc gọi rồi làm theo trí nhớ: tới lúc giao, hai bên nhớ khác nhau.\n\n## Làm thế nào\n\n1. Chép lại yêu cầu vào hồ sơ (issue, `REQ-…`).\n2. Gửi lại: *\"Tôi hiểu thế này, đúng không?\"*\n3. Chỉ bắt đầu khi người đặt yêu cầu xác nhận.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['phan-loai-yeu-cau', 'Phân loại: nhỏ, thường, lớn, sự cố', 'Cỡ việc quyết định phải đi qua những bước nào.', "| Cỡ | Ví dụ | Phải có |\n|---|---|---|\n| Nhỏ | Sửa chữ | Commit rõ, test pass |\n| Thường | Thêm một màn hình | Đề xuất, xác nhận, test |\n| Lớn | Tính năng mới cả cụm | Hồ sơ yêu cầu, ADR, nghiệm thu |\n| Sự cố | Ứng dụng hỏng | Khắc phục trước, điều tra sau |\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'lam-ro-yeu-cau' => ['Làm rõ yêu cầu', 'Hỏi gì, hỏi ai, khi nào thì đủ rõ để làm.', [
            ['bo-cau-hoi-lam-ro', 'Bộ câu hỏi làm rõ', 'Mục tiêu, người dùng, phạm vi, dữ liệu, nghiệm thu.', "## Hỏi gộp một lần, đánh số\n\n1. Giải quyết vấn đề gì, cho ai?\n2. Cái gì chắc chắn **không** làm?\n3. Lỗi, dữ liệu rỗng, không có quyền thì sao?\n4. Ai nghiệm thu, bằng cách nào?\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['tieu-chi-chap-nhan', 'Viết tiêu chí chấp nhận kiểm được', 'Cho trước / Khi / Thì, có số cụ thể.', "```text\nCho trước tôi đã đăng nhập\nKhi tôi bấm ☆ trên một bài viết\nThì bài viết hiện trong My lists\n```\n\nTiêu chí tốt thì người kiểm thử kiểm được mà không đọc code.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'phan-tich' => ['Phân tích', 'Phân tích ảnh hưởng: bảng, API, màn hình, quyền nào thay đổi.', [
            ['phan-tich-anh-huong', 'Phân tích ảnh hưởng trước khi làm', 'Liệt kê mọi chỗ sẽ thay đổi để ước tính đúng.', "| Phần | Câu hỏi |\n|---|---|\n| DB | Thêm bảng, cột nào? Có phải chuyển dữ liệu cũ? |\n| API | API nào mới, API nào đổi dạng dữ liệu? |\n| Giao diện | Màn hình nào thay đổi? |\n| Quyền | Ai được làm gì? |\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['uoc-tinh', 'Ước tính bằng khoảng, kèm điều kiện', 'Không hứa một con số chắc chắn.', "Trả lời *\"2–3 buổi, nếu câu hỏi 2 được trả lời trước thứ Tư\"* thay vì *\"2 ngày\"*.\n\nViệc hơn 6 buổi thì tách nhỏ.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'thiet-ke' => ['Thiết kế', 'Đưa phương án, xin duyệt, ghi quyết định vào ADR.', [
            ['trinh-bay-phuong-an', 'Trình bày phương án để xin duyệt', 'Bắt đầu từ vấn đề, ít nhất hai phương án, có đề xuất.', "1. Vấn đề là gì (một đoạn).\n2. Phương án A, B kèm đánh đổi.\n3. Khuyên chọn gì, vì sao.\n4. Rủi ro và cách quay lui.\n5. Hỏi rõ: *\"Anh chọn A hay B?\"*\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['ghi-quyet-dinh-adr', 'Ghi quyết định bằng ADR', 'Mỗi quyết định một file, giữ mãi.', "ADR gồm: bối cảnh, quyết định, các phương án đã xét, hệ quả.\n\nADR đã chấp nhận thì không sửa nội dung: viết ADR mới thay nó.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'thuc-hien' => ['Thực hiện', 'Làm code, commit nhỏ, review.', [
            ['commit-nho', 'Commit nhỏ, mỗi commit một mục đích', 'Dễ review, dễ quay lui.', "```text\nfeat(api): end users sign in with a username\n```\n\nCommit nào cũng phải build được.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['lam-viec-voi-reviewer', 'Làm việc với người review', 'Trả lời mọi góp ý: sửa hoặc giải thích.', "- Pull request nhỏ, có mô tả: làm gì, vì sao, test thế nào.\n- Góp ý vào code, không vào người.\n- Không tự đóng góp ý còn đang tranh luận.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'kiem-thu' => ['Kiểm thử', 'Test theo rủi ro: đơn vị, tích hợp, trình duyệt.', [
            ['test-theo-rui-ro', 'Test theo rủi ro, không theo phần trăm', 'Cái gì hỏng thì đau nhất, test rẻ nhất nào bắt được?', "Nhiều test đơn vị và tích hợp, ít test trình duyệt.\n\nKiểm cả **thứ tự** và **danh tính**, không chỉ \"có kết quả\".\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['bao-loi-tot', 'Báo lỗi sao cho người khác làm lại được trong 5 phút', 'Bước làm, mong đợi, thực tế, bằng chứng.', "1. Tiêu đề: triệu chứng + chỗ xảy ra.\n2. Môi trường, commit.\n3. Các bước chính xác.\n4. Mong đợi / thực tế.\n5. Ảnh, dòng log, mã request.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'qa' => ['QA và nghiệm thu', 'Kế hoạch test, thử khám phá, biên bản nghiệm thu.', [
            ['ke-hoach-test', 'Kế hoạch test trước khi làm', 'Phạm vi, dữ liệu, phần tự động, phần thử tay.', "Viết kế hoạch test vào hồ sơ yêu cầu **trước** khi code: mỗi tiêu chí chấp nhận ứng với ít nhất một test.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['lam-viec-voi-qa', 'Làm việc với người kiểm thử', 'Giao việc rõ ràng, nhận lỗi không tự ái.', "- Ghi rõ commit, môi trường, chỗ rủi ro khi giao kiểm.\n- Nhận báo lỗi: làm lại lỗi, viết test bắt lỗi, rồi mới sửa.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'trien-khai' => ['Triển khai', 'Phát hành, đánh số phiên bản, quay lui.', [
            ['cac-buoc-phat-hanh', 'Các bước phát hành', 'Kiểm tra, ghi chú phát hành, sao lưu, tag, theo dõi.', "1. Kiểm tra tự động pass.\n2. Ghi chú phát hành.\n3. Sao lưu.\n4. Gắn tag.\n5. Nâng cấp, xem log 15 phút.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['quay-lui-truoc', 'Quay lui trước, điều tra sau', 'Quay lui không phải thất bại.', "| Hỏng gì | Làm gì |\n|---|---|\n| Code | Chạy lại bản trước |\n| Migration | Quay lui migration |\n| Dữ liệu | Khôi phục bản sao lưu |\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'ban-giao' => ['Bàn giao', 'Người nhận làm tiếp được mà không phải hỏi lại.', [
            ['tai-lieu-ban-giao', 'Tài liệu bàn giao tối thiểu', 'Chạy, kiến trúc, tính năng, quyết định, vận hành, việc còn mở.', "Ngồi cùng người nhận chạy thử một lần theo tài liệu, sửa chỗ họ vấp.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['lam-viec-voi-ops', 'Làm việc với người vận hành', 'Báo cấu hình mới trước ngày phát hành.', "Biến môi trường, cổng, migration nặng: báo **trước**, ghi vào mục nâng cấp của ghi chú phát hành.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
        'van-hanh' => ['Vận hành và sự cố', 'Giám sát, xử lý sự cố, nhìn lại không đổ lỗi.', [
            ['xu-ly-su-co', 'Xử lý sự cố', 'Khắc phục tạm trước, rồi điều tra.', "1. Phát hiện.\n2. Phân loại mức, cử người chỉ huy.\n3. **Khắc phục tạm**.\n4. Báo tình hình.\n5. Điều tra, ghi dòng thời gian.\n6. Nhìn lại trong 5 ngày.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
            ['nhin-lai-khong-do-loi', 'Nhìn lại không đổ lỗi', 'Hệ thống đã để lỗi xảy ra thế nào, không phải ai sai.', "Viết *\"script phát hành không kiểm tra migration\"*, không viết *\"X quên\"*.\n\n> Nội dung mẫu: chủ dự án cập nhật sau."],
        ]],
    ];

    /**
     * Categories of the skills group keyed by slug: [name, description, attachments as [title, url, note]].
     *
     * @var array<string, array{string, string, list<array{string, string, string}>}>
     */
    private const SKILLS = [
        'laravel' => ['Laravel', "Dựng API cho Second Memory: phân quyền động, hàng đợi, upload file lớn.\n\n> Nội dung mẫu: chủ dự án cập nhật sau.", [
            ['Tài liệu Laravel', 'https://laravel.com/docs', 'Nơi tra cứu chính'],
        ]],
        'postgresql' => ['PostgreSQL', "Tìm kiếm tiếng Việt không dấu, index, đọc query plan.\n\n> Nội dung mẫu: chủ dự án cập nhật sau.", [
            ['Tài liệu PostgreSQL', 'https://www.postgresql.org/docs/', 'Phần full text search'],
        ]],
        'docker' => ['Docker', "Môi trường dev chạy hoàn toàn trong container trên WSL.\n\n> Nội dung mẫu: chủ dự án cập nhật sau.", []],
        'nextjs' => ['Next.js', "Trang quản trị: App Router, TanStack Query.\n\n> Nội dung mẫu: chủ dự án cập nhật sau.", []],
    ];

    /**
     * A book: [slug, title, summary, body, category slugs, sub-pages] (ADR-0022).
     *
     * @var array{string, string, string, string, list<string>, list<mixed>}
     */
    private const BOOK = ['laravel-thuc-chien', 'Laravel thực chiến', 'Ghi chép khi dựng API của Second Memory, chia theo chương.', "Cuốn sổ tay này gom kinh nghiệm dựng API bằng Laravel.\n\n## Đọc theo thứ tự\n\nMỗi chương là một bài riêng, xem ở menu bên trái.\n\n> Nội dung mẫu: chủ dự án cập nhật sau.", ['laravel', 'thuc-hien'], [
        ['cai-dat-du-an', 'Cài đặt dự án', 'Chạy trong Docker, cấu hình môi trường.', "## Các bước\n\n1. `make up`\n2. `make seed`\n\n> Nội dung mẫu.", [], []],
        ['hang-doi', 'Hàng đợi', 'Việc nền chạy ngoài request.', "## Khi nào dùng\n\nViệc lâu hơn vài giây: ghép file, tạo ảnh nhỏ.\n\n> Nội dung mẫu.", ['laravel'], [
            ['redis-cho-hang-doi', 'Redis cho hàng đợi', 'Cấu hình và giám sát.', "## Cấu hình\n\n`QUEUE_CONNECTION=redis`\n\n> Nội dung mẫu.", [], []],
            ['xu-ly-viec-loi', 'Xử lý việc lỗi', 'Thử lại, báo lỗi, xem failed_jobs.', "## Thử lại có giới hạn\n\nSau số lần thử, việc vào `failed_jobs`.\n\n> Nội dung mẫu.", [], []],
        ]],
        ['trien-khai-laravel', 'Triển khai', 'Từ máy dev tới môi trường giống production.', "## Kiểm tra trước\n\n`make ci` phải xanh.\n\n> Nội dung mẫu.", ['trien-khai'], []],
    ]];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DevContentSeeder never runs in production.');
        }

        $groups = [];
        foreach (array_keys(self::GROUPS) as $position => $slug) {
            [$name, $description] = self::GROUPS[$slug];
            $groups[$slug] = $this->category($slug, $name, $description, null, $position + 1)->id;
        }

        $categories = [];
        foreach (array_keys(self::PROCESS) as $position => $slug) {
            [$name, $description, $articles] = self::PROCESS[$slug];
            $categories[$slug] = $this->category($slug, $name, $description, $groups['quy-trinh'], $position + 1)->id;
            foreach ($articles as $order => [$articleSlug, $title, $summary, $body]) {
                $this->article($articleSlug, $title, $summary, $body, [$categories[$slug]], null, $order + 1);
            }
        }

        foreach (array_keys(self::SKILLS) as $position => $slug) {
            [$name, $description, $attachments] = self::SKILLS[$slug];
            $category = $this->category($slug, $name, $description, $groups['ky-nang'], $position + 1);
            $categories[$slug] = $category->id;
            if (! CategoryAttachment::query()->where('content_category_id', $category->id)->exists()) {
                foreach ($attachments as $order => [$title, $url, $note]) {
                    CategoryAttachment::create(['content_category_id' => $category->id, 'title' => $title, 'url' => $url, 'note' => $note, 'position' => $order]);
                }
            }
        }

        $this->book(self::BOOK, null, 1, $categories);

        $this->command->info('Sample content ready: '.count(self::GROUPS).' groups, '.(count(self::PROCESS) + count(self::SKILLS)).' categories, a book.');
    }

    /**
     * A group or category matched by slug. A category seeded before groups existed joins its group.
     */
    private function category(string $slug, string $name, string $description, ?int $parentId, int $position): ContentCategory
    {
        $category = ContentCategory::firstOrCreate(['slug' => $slug], [
            'parent_id' => $parentId,
            'name' => $name,
            'description' => $description,
            'sort_order' => $position,
            'is_public' => true,
        ]);
        if ($parentId !== null && $category->parent_id === null) {
            $category->update(['parent_id' => $parentId]);
        }

        return $category;
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function article(string $slug, string $title, string $summary, string $body, array $categoryIds, ?int $parentId, int $position): Article
    {
        $article = Article::firstOrCreate(['slug' => $slug], [
            'parent_id' => $parentId,
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'sort_order' => $position,
            'is_public' => true,
        ]);
        $article->categories()->syncWithoutDetaching($categoryIds);

        return $article;
    }

    /**
     * @param  array{string, string, string, string, list<string>, list<mixed>}  $page
     * @param  array<string, int>  $categories  ids by slug
     */
    private function book(array $page, ?int $parentId, int $position, array $categories): void
    {
        [$slug, $title, $summary, $body, $categorySlugs, $children] = $page;
        $ids = array_map(fn (string $categorySlug): int => $categories[$categorySlug], $categorySlugs);
        $article = $this->article($slug, $title, $summary, $body, $ids, $parentId, $position);
        foreach ($children as $order => $child) {
            /** @var array{string, string, string, string, list<string>, list<mixed>} $child */
            $this->book($child, $article->id, $order + 1, $categories);
        }
    }
}
