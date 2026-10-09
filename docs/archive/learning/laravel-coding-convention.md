> **Learning note (archived 2026-10-09, HK-01; was `laravel-api/docs/CodingConvention.md`).** The owner's first Laravel coding convention (Master / Management / History modules, interfaces per repository, CRUD generator). The current rules are [`.claude/rules/backend-laravel.md`](../../../.claude/rules/backend-laravel.md), [handbook 05](../../handbook/05-coding.md) and [`laravel-api/CLAUDE.md`](../../../laravel-api/CLAUDE.md); the modules it describes were removed in RFC-001. Kept unchanged otherwise.
>
> 🇻🇳 **Ghi chú học tập, đã lưu trữ (2026-10-09).** Quy ước code Laravel đầu tiên của owner. Quy tắc hiện tại: `.claude/rules/backend-laravel.md`, handbook 05 và `laravel-api/CLAUDE.md`; các module nó mô tả đã bị xoá trong RFC-001. Nội dung còn lại giữ nguyên.

# Quy ước mã hóa cho dự án Laravel API

## Mục lục

1. [Giới thiệu](#giới-thiệu)
2. [Tổng quan cấu trúc dự án API](#tổng-quan-cấu-trúc-dự-án-api)
3. [Luồng hoạt động của một API Request](#luồng-hoạt-động-của-một-api-request)
4. [Cấu trúc thư mục chuẩn](#cấu-trúc-thư-mục-chuẩn)
5. [Quy ước chung](#quy-ước-chung)
6. [Thiết kế cơ sở dữ liệu](#thiết-kế-cơ-sở-dữ-liệu)
7. [Migration](#migration)
8. [Model](#model)
9. [Common Response](#common-response)
10. [Bootstrap/app.php](#Bootstrap/app.php)
11. [Middleware](#middleware)
12. [Controller](#controller)
13. [Service](#service)
14. [Validate Request (Form Request)](#validate-request-form-request)
15. [Interface & Repository](#interface--repository)
16. [API Resources](#api-resources)
17. [Routing](#routing)
18. [Các bước thực hiện khi làm việc với API](#các-bước-thực-hiện-khi-làm-việc-với-api)

## Giới thiệu

Tài liệu này định nghĩa các quy ước và kiến trúc chuẩn cho việc phát triển API dựa trên nền tảng Laravel, tuân thủ tiêu chuẩn PSR-2/PSR-12. Mục tiêu là đảm bảo tính nhất quán, dễ mở rộng, bảo trì và dễ tiếp cận cho các thành viên mới.

## Tổng quan cấu trúc dự án API

Dự án được thiết kế theo kiến trúc **Layered Architecture** (Service Layer, Repository Layer, Domain Layer) kết hợp với **Module-based Organization**. Các module được tổ chức theo phạm vi: **Master** (dữ liệu gốc), **Management** (dữ liệu quản lý), và **History** (lịch sử).

## Luồng hoạt động của một API Request

### Luồng xử lý (Request Flow)

`Route` -> `Middleware` -> `Controller` -> `Form Request (Validation)` -> `Service` -> `Repository` -> `Model` -> `Database`

### Ý nghĩa của các thành phần

- **Route:** Định nghĩa điểm cuối (endpoint) của API.
- **Controller:** Tiếp nhận HTTP request, điều phối request, gọi Service, và trả về response qua API Resource. Không chứa logic nghiệp vụ.
- **Form Request:** Xác thực dữ liệu đầu vào.
- **Service:** Chứa logic nghiệp vụ.
- **Interface & Repository:** Trừu tượng hóa truy xuất dữ liệu.
- **Model:** Đại diện cho bảng trong cơ sở dữ liệu.
- **API Resource:** Biến đổi dữ liệu thành định dạng JSON.
- **Middleware:** Xử lý logic trước/sau khi request đến Controller (xác thực, phân quyền, logging).
- **Bootstrap/app.php withExceptions:** Xử lý exception hoặc sự kiện đặc biệt.

## **Cấu trúc thư mục chuẩn**

Cấu trúc thư mục được tổ chức theo nghiệp vụ và loại đối tượng, phân loại theo `Master`, `Management`, và `History`.

```plaintext
app/
├── Http/
│   ├── Controllers/
│   │   ├── History/
│   │   │   ├── Master/
│   │   │   │   └── CategoryMstHistController.php
│   │   │   └── Management/
│   │   │       └── CategoryMgmtHistController.php
│   │   ├── Master/
│   │   │   └── CategoryMstController.php
│   │   └── Management/
│   │       └── ProductMgmtController.php
│   ├── Middleware/
│   ├── Requests/
│   │   ├── History/
│   │   │   ├── Master/
│   │   │   │   ├── CategoryMstHistListRequest.php
│   │   │   │   └── StoreCategoryMstHistRequest.php
│   │   │   └── Management/
│   │   │       ├── UpdateCategoryMgmtHistRequest.php
│   │   │       └── DeleteCategoryMgmtHistRequest.php
│   │   ├── Master/
│   │   │   └── CategoryMstRequest.php
│   │   └── Management/
│   │       └── ProductMgmtRequest.php
│   └── Resources/
│       ├── History/
│       │   ├── Master/
│       │   │   └── CategoryMstHistResource.php
│       │   └── Management/
│       │       └── CategoryMgmtHistResource.php
│       ├── Master/
│       │   └── CategoryMstResource.php
│       └── Management/
│           └── ProductMgmtResource.php
├── Interfaces/
│   ├── History/
│   │   ├── Master/
│   │   │   └── CategoryMstHistInterface.php
│   │   └── Management/
│   │       └── CategoryMgmtHistInterface.php
│   ├── Master/
│   │   └── CategoryMstInterface.php
│   └── Management/
│       └── ProductMgmtInterface.php
├── Repository/
│   ├── History/
│   │   ├── Master/
│   │   │   └── CategoryMstHistRepository.php
│   │   └── Management/
│   │       └── CategoryMgmtHistRepository.php
│   ├── Master/
│   │   └── CategoryMstRepository.php
│   └── Management/
│       └── ProductMgmtRepository.php
├── Models/
│   ├── Master/
│   │   └── CategoryMst.php
│   ├── Management/
│   │   └── ProductMgmt.php
│   └── History/
│       ├── Master/
│       │   └── CategoryMstHist.php
│       └── Management/
│           └── ProductMgmtHist.php
├── Providers/
│   └── RepositoryServiceProvider.php
└── Services/
    ├── Master/
    │   └── CategoryMstService.php
    ├── Management/
    │   └── ProductMgmtService.php
    └── History/
        ├── Master/
        │   └── CategoryMstHistService.php
        └── Management/
            └── ProductMgmtHistService.php

database/
└── migrations/
    ├── Table/
    │   ├── History/
    │   │   ├── Master/
    │   │   │   └── 2025_06_20_000000_create_category_mst_hist_table.php
    │   │   └── Management/
    │   │       └── 2025_06_20_000000_create_category_mgmt_hist_table.php
    │   ├── Master/
    │   │   └── 2025_06_20_000000_create_category_mst_table.php
    │   └── Management/
    │       └── 2025_06_20_000001_create_product_mgmt_table.php
    └── View/
    └── Procedure/
    └── ... (etc)
```

## Quy ước chung

- **Chuẩn code:** Tuân thủ **PSR-12**.
- **Ngôn ngữ:** Sử dụng tiếng Anh số ít cho tên file, class, method, variable, và comment.
- **Quy ước đặt tên:**

| Thành phần            | Quy ước         | Ví dụ                     |
|-----------------------|-----------------|---------------------------|
| Class (Model, Controller, Service...) | PascalCase, số ít | `ProductService`          |
| Method, Variable      | camelCase       | `getAllProducts`          |
| Hằng số (Constants)   | CONSTANT_CASE   | `STATUS_ACTIVE = 1`       |
| Bảng và Cột trong DB  | snake_case      | `product_mgmt`, `rank_order` |
| Tên file Route        | kebab-case, tham số snake_case | `/api/user-permissions?created_from=2024-01-01` |

  ** **GET:** `/api/user-permissions?created_from=2024-01-01&is_active=true`
  ** **POST:** `/api/user-permissions`
  ** **UPDATE:** `/api/user-permissions/{123}`
  ** **DELETE:** `/api/user-permissions/{123}`

## Thiết kế cơ sở dữ liệu

- **Table:**
  - **Định danh:** `snake_case`, số ít (Vd: `user`, `product`).
  - **Hậu tố `_mst`:** Dữ liệu gốc (Vd: `category_mst`).
  - **Hậu tố `_mgmt`:** Dữ liệu quản lý (Vd: `product_mgmt`).
  - **Hậu tố `_hist`:** Lịch sử (Vd: `product_mgmt_hist`).
  - **Bảng trung gian (N-N):** Tên gồm 2 bảng, theo thứ tự alphabet (Vd: `product_tag_mgmt`).

- **Column:**
  - **Định danh:** `snake_case`, số ít, không viết tắt, tiếng Anh.
  - **Khóa chính:** `id`, kiểu `bigIncrements` hoặc `ulid`.
  - **Khóa ngoại:** `{table}_id` (Vd: `category_mgmt_id`).
  - **Boolean:** Tiền tố `is_` hoặc `has_` (Vd: `is_active`).
  - **Timestamps:** `created_at`, `updated_at` kiểu `timestamp`.

- **Procedure:** `{function_name}_{scope}_function` (Vd: `calculate_category_mst_function`).
- **View:** `view_{view_name}_{scope}` (Vd: `view_category_list_mst`).
- **Trigger:** `trigger_{action}_{time}_{action}_{table}_{scope}` (Vd: `trigger_insert_before_insert_category_mst`).
- **Sequence:** `{table}_{scope}_seq` (Vd: `category_mst_seq`).

* **Loại dữ liệu:** các column cần phân tích kiểu và kích thước tối thiểu hoặc tối đa mà dữ liệu cần sử dụng hiện tại và mở rộng trong tương lai để đưa ra để đảm bảo khả năng lưu trữ. Có thể dựa theo các quy chuẩn quốc tế về các loại dữ liệu và mục đích sử dụng để triển khai. Ví dụ kích thước cho số điện thoại là 12 kí tự, email là 320 kí tự,...

* **Các định nghĩa khác:** cần phân tích phạm vi, khả năng sử dụng của từng column để định nghĩa các thuộc tính default, not null, unique. Tối ưu sử dụng cho từng column, chỉ định primary key

* **Chú thích và giải thích:** Cần viết định nghĩa ngắn gọn về ý nghĩa. Viết hoa chữ cái đầu. Giải thích chi tiết về cách chúng sử dụng cho việc gì ? có những loại nào ? khi nào dùng ở trong 1 bộ tài liệu riêng.

## Migration

- **Lệnh tạo:**
  ```bash
  php artisan make:migration [create|update]_[table name]_[table] --path=[scope]
  ```
- **Nội dung:**
  - Dùng `Schema::create` hoặc `Schema::table` cho bảng.
  - Dùng `DB::statement` hoặc `DB::unprepared` cho view, procedure, trigger, sequence trong `up()`.
  - Viết logic rollback trong `down()`.


## Const

- **Quy ước nội dung file const:**
  - Nếu table có định nghĩa column dạng status, tạo mới enum nếu chưa có
  - Tên file enum là tên status đó, nội dung file định nghĩa key và value tương ứng mỗi loại status của status đó

## Message

- **Quy ước nội dung file message:**
  - Sử dụng column name làm key và comment làm message. Định nghĩa chúng trong file resources/lang/en/messages.php
  - File này return về array, bên trong chứa key/value các message common

## Model

- **Lệnh tạo:**
  ```bash
  php artisan make:model [table name] --path=[scope]
  ```

- **Quy ước nội dung file model:**
  - **Table:** `protected $table = '[table name]';`.
  - **Fillable:** `protected $fillable = ['column1', 'column2',...];`.
  - **Quan hệ:** Tên hàm `camelCase`:
    ```php
    public function [tableName](): [hasOne|HasMany|BelongsTo|BelongsToMany]
    {
        return $this->[hasOne|HasMany|BelongsTo|BelongsToMany]([ModelName]::class);
    }
    ```

## Interface & Repository

- **Interface:**
  ```php
  interface [table name]Interface {
      public function [method Name]([param Name]);
  }
  ```

- **Repository:**
  ```php
  class [table name]Repository implements [InterfaceName]Interface {
      // Implement methods
  }
  ```
  + Logic get list (param payload):
        select *
        from
            * Nếu là Table trung gian: from table left join với table trung gian và left join với table liên kết với table trung gian
            * Nếu là Table liên kết N-1: from table N inner join với table 1
            * Nếu là Table liên kết 1-N: from table 1
        where
      Mỗi column của table đem ra kiểm tra param truyền vào có key đó không. Nếu có thì compare query column đó với payload có key tương ứng
        order by table.id
  + Logic create (param payload):
    Mỗi column của table đem ra kiểm tra param truyền vào có key đó không.
    Nếu có thì gán value đó tương ứng với các column trong table rồi mới save
  + Logic update (param payload, id):
    Từ id check tồn tại record có id đó trong table hiện tại không. Ko thì văng exception. Nếu có, gán từng column table ứng với payload có key tương ứng

- **Binding:** Trong `app/Providers/RepositoryServiceProvider.php`:
  ```php
  $this->app->bind(
      \App\Interfaces\[scope]\[InterfaceName]Interface::class,
      \App\Repositories\[scope]\[RepositoryName]Repository::class
  );
  ```

## API Resources

- **Lệnh tạo:**
  ```bash
  php artisan make:resource [resource Name]Resource --path=[scope]
  ```

- **Ví dụ:**
  ```php
  public function toArray($request): array
  {
      return [
          'id' => $this->id,
          'productName' => $this->name,
          'price' => $this->price,
          'isActive' => $this->is_active,
          'category' => new CategoryResource($this->whenLoaded('category')),
          'createdAt' => $this->created_at->toIso8601String(),
      ];
  }
  ```

## Service

- **Vị trí:** `[scope]/[serviceName]Service.php`.
- **Định dạng:**
  ```php
  /**
   * [Nhiệm vụ của method]
   * @param [dataType] [param]
   * @return [dataType]
   */
  public function [functionName]([dataType] [param]): [dataType]
  {
      $variable = [RepositoryName]Repository::[methodName]([param]);
      return $variable ? [ResourceName]Resource::collection($variable) : [];
  }
  ```
- Dependence injection trực tiếp trong constructor của controller
+ logic delete: 
  * Nếu là Table trung gian (table liên kết giữa các table): Xóa ko cần điều kiện
  * Nếu là Table liên kết N-1: Xóa ko cần điều kiện
  * Nếu là Table liên kết 1-N: Kiểm tra tồn tại ở model N đó, nếu ko tồn tại thì mới được xóa
  * Common: Trước khi xóa cái gì cần kiểm tra tồn tại mới xóa
+ Logic create:
  * Nếu là Table trung gian (table liên kết giữa các table): Check tồn tại table liên kết rồi mới tạo
  * Nếu là Table liên kết N-1: Tạo thêm logic check tồn tại table 1 thì mới tạo
  * Nếu là Table liên kết 1-N: Tạo không cần thêm logic


## Common Response

- **Ý nghĩa:** Chuẩn hóa response API với `$data` (dữ liệu chính) và `$error` (status, code, messages).
- **Định nghĩa:**
  ```php
  namespace App\Traits;

  trait ApiResponse {
      /**
       * Render response api
       *
       * @param mixed $data
       * @param array $error
       * @return Response
       */
      public static function renderResponse(mixed $data, array $error): Response
      {
          list($status, $code, $messages) = $error;

          return response()->json([
              'data' => $data,
              'error' => [
                  'status' => $status,
                  'code' => $code,
                  'messages' => $messages
              ]
          ]);
      }
  }
  ```

## Bootstrap/app.php

- **Ý nghĩa withExceptions:** Xử lý các exception.
  Tổng hợp xử lý cho các loại exception khác nhau
* **Định nghĩa**
  - Truy cập file `boostrap/app.php` method withExceptions
  - Định nghĩa các cách render ứng với từng loại exception
  - Sử dụng trait ApiResponse để chuẩn hóa các respose trả về
- **Ý nghĩa:** Bất kỳ xử lý lỗi nào hãy throw exception và message nếu có. Ở đây sẽ sử lý phân loại và trả về response exception sau cùng

## Middleware

* **Nhiệm vụ**
  - Xử lý các logic chung trước hoặc sau khi request đến Controller (xác thực, phân quyền, logging, CORS, quản lý transaction...).

* **Quy ước**
  - Tạo một middleware mới, ví dụ: `TransactionMiddleware`. Middleware này sẽ tự động bắt đầu transaction trước khi request được xử lý ở controller. Nếu có bất kỳ exception nào xảy ra trong quá trình xử lý, transaction sẽ tự động rollback; nếu xử lý thành công, transaction sẽ được commit.
  - Tạo một middleware, GenerateResponseMiddleware. Dùng để kết xuất response thành công có cùng 1 format
  - Tạo middleware, AdminMiddleware dùng để check user hiện tại có được access vào route request hiện tại không
  - Mỗi Middleware chỉ nên thực hiện một nhiệm vụ duy nhất.
  - Đăng ký middleware này trong `app/Http/Kernel.php` và gán cho các route phù hợp, thường là các route sử dụng method POST, PUT, DELETE.

* **Ví dụ tạo middleware:**
  ```bash
  php artisan make:middleware TransactionMiddleware
  ```

* **Đăng ký middleware control DB transaction:**
  - Thêm vào `$routeMiddleware` trong `app/Http/Kernel.php`:
    ```php
    'db.transaction' => \App\Http\Middleware\DatabaseTransactionMiddleware::class,
    ```
  - Sử dụng cho các route cần quản lý transaction:
    ```php
    Route::middleware(['db.transaction'])->group(function () {
        // Các route POST, PUT, DELETE
    });
    ```
  - Sử dụng cho các route group API, cái này áp dụng cho toàn bộ API trong api.php:
    ```php
     'api' => [
          // ...existing code...
          // Thêm middleware transaction cho API
          \App\Http\Middleware\DatabaseTransactionMiddleware::class,
      ],
    ```


## Validate Request (Form Request)
* **Lệnh tạo:**
  ```bash
  php artisan make:request [request name]Request --path=[scope]
  ```
** **[validate name]** Dựa vào tên [controller name] + [tên method] + hậu tố "Request" dạng **PascalCase** để đặt tên cho validate. VÍ dụ: CategoryController.php có function store() thì tạo tên file kiểu như sau: Category/CategoryStoreRequest.php. 
- Dependence injection trực tiếp trong các method của của controller 
* **Viết rule** Trong function rule() lấy tất cả param request, mỗi param viết rule riêng. Các param hầu hết tương ứng với column của 1 table cùng tên model, lấy các điều kiện migrate của table đó để xác định rule validate. Thực hiện, định nghĩa rule validate cho từng trường theo đúng kiểu dữ liệu và ràng buộc của migration
* **Định nghĩa attributes:** Mỗi column validate, định nghĩa name là các message column đã định nghĩa trong file lang/en/message.php 
- Inject form request trực tiếp vào các method của controller
* **Note** Tạo validate cho bất kỳ request: search by condition, store, update, delete.

## Controller

- **Lệnh tạo:**
  ```bash
  php artisan make:controller [table name]Controller --path=[scope]
  ```

- **Định dạng:**
  ```php
  class [table name]Controller extends Controller
  {
      /**
       * [Nhiệm vụ của method]
       * @param [dataType] [param]
       * @return [dataType]
       */
      public function [functionName]([dataType] [param]): [dataType]
      {
          return $this->[ServiceName]->[methodName]([param]);
      }
  }
  ```
- **Note :** Vì các controller được bọc và xử lý response thành công (xử lý ở middleware) và response thất bại (xử lý ở handle) nên mỗi controller hãy return thẳng service

## Routing

- **Vị trí:** `routes/api.php`.
- Tạo nếu chưa có prefix('admin')->group. Bên trong tạo nếu chưa có prefix(scope)->group
- Bên trong scope group đó, tạo group theo format
  ```php
  Route::prefix([table])->group(function () {
    Route::get('list', [table]Controller::class, 'list']);
    Route::post('store', [table]Controller::class, 'store']);
    Route::put('update/{id}', [table]Controller::class, 'update']);
    Route::delete('delete/{id}', [table]Controller::class, 'delete']);
  });
  ```

## Các bước thực hiện khi làm việc với API

**Xác định phạm vi** (master, management, history).
**Migration** (bao gồm table, view, procedure, sequence, trigger) và seed dữ liệu (nếu cần).
**Model** (Eloquent) và định nghĩa quan hệ.
**Repository** + **Interface** để tách rời logic truy cập dữ liệu.
**Service** để chứa Business Logic và validation bổ sung.
**Resource** (API Resource) để định dạng đầu ra chuẩn.
**Request Validation** (FormRequest) để định nghĩa rule và message.
**Controller** để chuyển tiếp request vào service và xử lý response.
**Route** khai báo trong file route module.

---
