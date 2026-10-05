### KHỞI TẠO

khi sử dụng minio để quản lý dữ liệu media, tôi cần thực hiện việc đầu tiên là khởi tạo, thực hiện khi build env:

* Cách minio scan clear: nó chạy một lần định kỳ mỗi 24H (mặc định), sau đó chúng mới thực hiện clear. Như vậy các file đã hết hạn vd rule quy định 24H, file đó đã tồn tại quá thời gian quy định nhưng chưa đến chu trình scan thì nó vẫn tồn tại. Đôi với minio phiên bản mới sẽ thực hiện quét liên tục nhưng tùy theo mức độ ưu tiên, số lượng object và tải của hệ thống, do đó có thể object đến hạn nhưng cần một thời gian sau chúng mới xóa là bình thường. Thời gian đó tùy thuộc vào tốc độ trên.

* Tạo 2 bucket để lưu trữ dữ liệu:
  * media-official: chứa dữ liệu riêng tư như thông tin cá nhân, tài liệu nhạy cảm, ...
    * Cần đánh dấu versioning để backup
    * Setting rule cho phép tồn tại file trong 30 ngày để có thể rollback, sau 30 ngày -> hard delete. 
    * Setting rule để dọn delete marker dư thừa
    * Setting rule để dọn các multipart upload thừa chưa hoàn tất. Thời gian 24H
    * Setting rule Expire Non-current Versions: chỉ giữ lại tối đa 3-5 phiên bản gần nhất. Xóa vĩnh viễn các phiên bản cũ hơn.
    * Nếu muốn khôi phục bất chế độ show version trên UI minio or dùng lệnh.
  * media-temp: chứa dữ liệu tạm thời, có set clear theo ngày
    * Bucket lưu trữ tạm thời setting lifecycle độc lập xóa dữ liệu tự động mỗi ngày, thường vài tiếng nó sẽ scan object với modified_time > 1 ngày -> xóa object
    * Không versioning để tiết kiệm chi phí lưu trữ vì không tạo delete marker, tự động clear dữ liệu mà không tồn rác
    * Setting rule để dọn các multipart upload thừa chưa hoàn tất. Thời gian 24H
    * Ý tưởng tạo một setting cho phép làm mới bucket temp này mỗi ngày thay vì các rule bên trên, nhưng không có.

  * Lưu trữ media sẽ theo format: '{workspace}/{year}/{month}/{uuid}.{extension}';
  * vd: media-official/2026/01/16/abc.jpg
  * Riêng upload media temp sẽ theo format: 'bucket/{uuid}.{extension}';

* Note:
  * MinIO sử dụng dấu / để mô phỏng cấu trúc thư mục. Nếu dồn quá nhiều đối tượng vào 1 prefix duy nhất sẽ gây áp lực truy vấn list và head. Khuyến nghị giữ đối tượng <10.000 đối tượng/prefix. Có thể chia thành nhiều prefix theo năm/tháng/ngày hoặc theo hash của object id.
  * Do đó, setting lifecycle tự động move media xuống tier lưu trữ thấp hơn, các media này là các media ít được sử dụng or lâu rồi không sử dụng or tần xuất truy cập ít và không muốn xóa, di chuyển nó xuống tier thấp hơn nhứ SSD -> HDD or cloud rẻ để tối ưu chi phí lưu trữ, truy vấn. Toàn bộ giao tiếp với dữ liệu đều thông qua giao thức HTTP(S) restful.

* Khi upload file nặng sẽ chia file thành nhiều part nhỏ để upload khác với upload file nhẹ sẽ upload one shot 1 lần duy nhất. Quy trình upload file nặng trong phạm vi storage là khởi tạo object (khu vực lưu trữ), upload các part vào đây, sau đó request confirm kết thúc complete upload, storage sẽ tự check, merge các part thành object và đánh dấu nó là hoàn thành hoặc thất bại. Mặc định, mọi multipart upload bị hủy (không hoàn tất) sẽ tự động bị xóa sau 24H và tần xuất quét xóa mặc định là 6H -> Nếu không cần thay đổi thiết lập thì việc này cũng tự động rồi.

* Khi có cơ chế backup object xóa mềm để có thể khôi phục dữ liệu khi cần trong một khoảng thời gian ngắn sau đó. không cần thiết phải lưu trữ chúng dài hạn làm ảnh hưởng đến dung lượng và hiệu xuất xử lý. Do đó cần thiết lập rule tự động xóa các file được đánh dấu xóa sau 30 ngày, trước đó 30 ngày cho phép rollback.

* Cơ chế versioning, mục đích chúng sinh ra là để bảo vệ dữ liệu và đảm bảo tính toàn vẹn của dữ liệu.
  * Tác dụng:
    * Chống ghi đè or xóa nhầm: khi ghi đè hoặc xóa nhầm file nếu không có version chúng sẽ bị xóa or thay ghi đè thay đổi. Dẫn đến việc dữ liệu cũ của object đó bị biến mất vĩnh viễn.
    * Chống ransomeware: Nếu hệ thống nhiễm mã độc và tất cả các file (object) bị mã hóa và không thể khôi phục. Thì thực chất chúng chỉ tạo ra một phiên bản khác của object đó và chỉ mã hóa dữ liệu 1 version mới của object đó thôi. Các dữ liệu version cũ của object đó vẫn an toàn, có thể khôi phục lại.
  * Cơ chế đánh dấu version: Mỗi khi object có sự thay đổi, chúng sẽ tạo ra version mới và đánh dấu nó làm phiên bản hiện tại, đối với việc xóa thì chúng sẽ chèn delete marker lên trên cùng và thông báo không tồn tại object đó khi tìm kiếm, nhưng thực chất chúng chưa được xóa hoàn toàn. Để thực sự xóa một dữ liệu cần chỉ định rõ phiên bản của chúng
  * Cơ Chế backup: Có 3 loại
    * Đồng bộ hóa các thay đổi: Sao chép dữ liệu các phiên bản mới nhất của object đó có nghĩa mỗi object chỉ lấy một phiên bản mới nhất
    * Khôi phục theo thời gian: Sao chép tất cả dữ liệu bao gồm việc lấy tất cả các phiên bản của mỗi object. Vì mỗi version mỗi object đều có timestamp tạo, hệ thống cho phép khôi phục dữ liệu tại một thời điểm trong quá khứ, bằng cách lấy toàn bộ dữ liệu cũ thông qua verion của các object có timestamp tạo <= thời điểm yêu cầu khôi phục trong quá khứ.
    * Khôi phục theo từng object: khôi phục dữ liệu cho từng object riêng lẻ, cách này xử lý cho các sự cố nhỏ chỉ có tác dụng thay đổi cho object đó mà không ảnh hưởng đến object khác or toàn bucket
  * Lưu ý dọn dẹp: Tác dụng của verioning rất tuyệt vời, nhưng chúng gây ra vấn đề khi một object thay đổi nhiều lần sẽ sao chép object ra số lượng tương ứng làm gây áp lực đến dung lượng lưu trữ và hiệu xuất sử dụng khi tương tác với object. Như vậy cần có rule dọn dẹp chúng.
    * Tự động xóa các version cũ không phải version hiện tại sau 30 ngày
    * Chỉ dữ lại 5 version gần nhất (setting phạm vi bucket, tất cả object trong bucket đó đều sẽ apply theo)
    * Tự động xóa các delete marker thừa trong một khoảng thời gian nhất định

* Cơ chế delete marker và xóa đối tượng: Nếu bucket bật tính năng versioning, thì mỗi khi xóa object đó chỉ là soft delete. Nó tạo delete marker để đánh dấu lại object đó. Client sẽ không nhìn thấy object đã xóa, nhưng thực tế chúng vẫn còn đang lưu trữ ở disk. Chức năng này có mục đích khôi phục dữ liệu, khi nhầm lẫn xóa object (do người dùng, lỗi logic delete) thì có thể khôi phục lại bằng cách xóa đánh dấu delete marker (current version). Vấn đề là object và delete marker lại không có liên kết ràng buộc lẫn nhau, nó tồn tại độc lập, nên khi xóa object thật vĩnh viễn thì delete marker vẫn còn tồn tại, lúc này delete marker là rác vì nó không đánh dấu cho object nào cả. Do đó cần setting rule để xóa vĩnh viễn delete marker. Vì bật tính năng versioning để cho mục đích khôi phục, nên cần setting rule như cái thùng rác, sẽ tự động xóa vĩnh viễn object sau x/ngày không khôi phục. Để đảm bảo quản lý, lưu trữ dữ liệu tối ưu.

* Setting IAM/policy để có thể có quyền thay đổi dữ liệu: upload, read-only, temp-only. Không dùng root access key cho app, giảm rủi do nhầm lẫn, tăng bảo mật.
  * Tạo file policy.json: Cho phép get, put, delete object phần resource bucket temp và official
  * Apply policy bằng lệnh mc policy add
  * Tạo user và apply policy bằng lệnh mc admin user add, đây chính là access key, secret key
  * Lấy giá trị này và tạo key + apply value vào file 
  * Kiểm tra có tồn tại /home/vinhdv/projects/my_life_management/laravel-api/.env thì tìm value access và secret key và thay thế value vào file .env, nếu không thì tạo mới access và secret key. Nếu không có file .env thì làm tương tự với file /home/vinhdv/projects/my_life_management/laravel-api/.env.example
  * VD: cấu hình .env dùng key
    AWS_ACCESS_KEY_ID=backend-user
    AWS_SECRET_ACCESS_KEY=strong-backend-password
    AWS_DEFAULT_REGION=us-east-1
    AWS_BUCKET=media
    AWS_ENDPOINT=http://minio:9000
    AWS_USE_PATH_STYLE_ENDPOINT=true
  * Thực hiện cấu hình trong file config/filesystem.php
    'disks' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
    ],
  * Kiểm tra lại luồng xử lý back-end, refactor lại sử dụng key này để thao tác dũ liệu thông qua minio

* Tiering: là tính năng của minio để tự động di chuyển dữ liệu giữa các tier lưu trữ khác nhau, ví dụ: SSD -> HDD or cloude rẻ để tối ưu chi phí lưu trữ, truy vấn. hiện tại bỏ qua làm mặc định phần này, mount 1 disk, chạy MinIO docker single-node. Trong tương lai sẽ chia ra các tier khác nhau để lưu trữ.
  * Pool SSD (hot)
  * Pool HDD (warm): vd dữ liệu sau 90 ngày không sờ vào -> move xuống HDD
  * Hoặc remote S3 (cold): vd dữ liệu sau 1 năm không sờ vào -> move xuống S3

* Tương lai sẽ thực hiện sau các thành phần: Backup & Disaster Recovery, Monitoring & Alert

* Cần có hard limit cho các bucket, tránh bị spam upload làm đầy dung lượng. Việc này đã được thiết lập khi build container storage này rồi. Ở FE sẽ thực hiện thông báo lỗi cho user trực quan về lỗi này. Phía monitor sẽ cần thiết lập gửi thông báo và cảnh báo dưng lượng lưu trữ này. Cái này tương lai làm.


#########################################################################################
#########################################################################################
#########################################################################################

# Phân loại và cách thức xử lý từng loại dữ liệu

* Thao tác với dữ liệu trong store (minio):
  * Sử dụng key/secrect để thao tác với minio, thường dùng ở back-end để xử lý các thao tác với minio như delete, rename, move, list, copy, ... Đây là các chức năng yêu cầu bảo mật cao, xử lý không quá nặng.
  * Sử dụng presigned url để thao tác với minio, thường dùng ở FE để xử lý các thao tác với minio như download, upload. Các chức năng này yêu cầu bảo mật thấp, xử lý nặng để front-end xử lý, sử dụng tài nguyên ở FE, giảm tải xử lý cho back-end làm tối ưu hiệu năng.

* Presigned url: Là url tạm thời, có thời gian tồn tại, sau thời gian tồn tại sẽ tự động xóa. Nó ứng dụng cho các thành phần có thể truy cập thao tác mà không cần đang nhập hay có secret key. Ứng dụng cho FE thao tác trực tiếp vói store. Chỉ dùng cho 1 action duy nhất, 1 object or 1 part number, 1 method, không list, đọc object khác, không upload part or object khác. Phù hợp với tính năng one-shot: upload, download, delete, rename, move với các file nhỏ. Hoặc muti-part upload, download. không quá nguy hiểm nên có thể set TTL rộng hơn 1 chút có thể đặt 10p cho upload part nặng chờ lâu và set thời gian ngắn khoảng 5-10s cho file nhỏ.

* File upload nhỏ không cần reconnect or retry.

* Đối với các chức năng one shot không cần cân nhắc dung lượng file: rename, move, delete. Vì vậy khi tạo presigned url có thể set TTL ngắn hơn khoảng 5-10s.

* Đối với các chức năng: upload, streaming, multi-part upload, download, streaming,... cần tính toán dung lượng file, tốc độ mạng, thời gian xử lý để tính toán thời gian tồn tại presigned url.

* Với streaming : Client request -> BE xử lý, hỗ trợ range header -> store minio để lấy -> response client. Bắt buộc hỗ trợ http range

============================================

* Vấn đề upload:
  * Php/laravel không phù hợp để xử lý file, stream, dễ bottleneck, dễ lỗi 429, chậm.
  * Nguyên tắc: laravel/php (api), chỉ đóng vai trò xác thực, một số chức năng oneshot, xử lý lưu trữ quản lý dữ liệu metadata. Còn lại xử lý file, stream, multipart upload, virus scan, backup, cleanup, ... sử dụng các cơ chế khác để thực hiện như: batch, queue, worker, cron, ...
  * Dùng lifecycle AbortIncompleteMultipartUpload cho bucket temp ở minio, để thực hiện clean up các part không được commit complete tự động -> không cấu thành object hoàn chỉnh, thời gian xóa 24H.
  * Upload file trực tiếp từ client -> minio thông qua presigned url. Sẽ sử dụng tài nguyên của client để tính toán xử lý file.
  * Ram chỉ giữ lại 1 số part rất nhỏ cùng thời điểm, cpu xử lý ít tiêu thụ ít xử lý nhanh, Disk gần như không dùng. Do đó không cần kiểm tra tài nguyên của client, nếu ram thấp -> upload chậm, cpu không đáng kể, mạng yếu timeout cho retry
  * Nếu browser crash thì multipart bị bỏ dở => lifecyle sẽ clean các part dư thừa không commit complete.
  * Các part upload lền minio sẽ được lưu trữ tạm thời, sau khi commit complete sẽ được merge lại thành object hoàn chỉnh.

* Nguyên lý: Xử lý upload file nặng và multipart trên minio
  * File được chia nhỏ thành nhiều part, mỗi part sẽ được upload lên minio độc lập. 
  * Vì minio không biết khi nào việc upload đã hoàn thành hay chưa. Mục đích cho phép retry/replace part và tránh object thiếu part, object lỗi.
  * Nếu không complete thì object đó sẽ không tồn tại, part sẽ lưu tạm thời và sẽ bị cleanup sau 1 thời gian (lifecycle). Do đó multipart upload xong cần commit complete bằng cách gửi request CompleteMultipartUpload với payload: uploadId, part chứa partNumber và etag tương ứng. Việc còn lại minio sẽ kiểm tra hợp lệ payload, ghép các part theo thứ tự, tạo thành object hoàn chỉnh, xóa dữ liệu tạm. 
  * Bạn đóng vai trò là người xác nhận, commit confirm hoàn thành upload, đóng hòm xử lý.

* Có 2 hình thức upload qua presiged url: Multipart upload (upload part, part là các phần trong 1 file được chia nhỏ) và Single upload (upload 1 file nhỏ)

* Bản chất upload file là đính kèm luồng byte (binary stream) bên trong request và gửi thông qua HTTP. Với file nhỏ số byte ít, thời gian xử lý ngắn nên không bị timeout, tốc độ truyền tải dữ liệu mạnh khiến file nhỏ được xử lý rất nhanh fail thì retry lại dễ chịu hơn. Ngược lại file lớn, khiến request rất nặng do browser gửi file theo stream, OS + TCP stack từng phần nhỏ, request xử lý quá lâu bị timeout, thời gian xử lý lâu khiến user có thể thực hiện hành động khác nhau như reload, off, sleep,... khiến nó bị fail và việc retry lại cảm giác khi chịu. Việc upload 1 lần file nặng không tận dụng hết băng thông, khó control kết quả, UX tệ hại.

# Công thức ước lượng và tính toán
  * `Tính toán tốc độ upload để hiển thị cho user`
    * percent = (loaded/total) * 100
      * loaded: Tổng số byte của các part đã xong + số byte đang tải của các part hiện tại
      * Total: Tổng dung lượng file ban đầu (đơn vị: byte)

  * `Công thức tính toán thời gian thử lại`
    * t_delay = t_base * 2^n + random(0, 100)
      * t_base: thời gian chờ cơ bản (eg: 1000s)
      * n: số lần thử lại thất bại (0, 1, 2, 3)
      * random(0, 100): độ ngẫu nhiên, tránh việc nhiều máy, nhiều request thử lại cùng 1 thời điểm
      * Nếu n >= 3, dừng quá trình và báo lỗi

  * `Công thức tính số lượng part song song`
    | HTTP | C_http |
    |------|--------|
    | HTTP/1.1 | <= 6 |
    | HTTP/2 | 8 – 16 |
    | HTTP/3 | 16 – 32 |

  * `Công thức tính toán part size`
    | File size | S_spec |
    |-----------|--------|
    | <100 MB | ko multipart, dùng single upload object |
    | 100MB - 500 MB | max(16MB, ceil(FileSize / 10_000)) |
    | 500MB - 10GB | max(32MB, ceil(FileSize / 10_000)) |
    | 10 – 100 GB | max(64MB, ceil(FileSize / 10_000)) |
    | >100 GB | max(128MB, ceil(FileSize / 10_000)). 128MB chuẩn hiệu xuất, nếu kết quả lớn hơn 128MB thì lấy để đảm bảo nó không vượt qua giới hạn 10.000 part |

  * `Công thức tính TTL cho presigned url`
    | File size | TTL presign |
    |-----------|-------------|
    | ≤100MB | 300s |
    | 100MB–10GB | 60s |
    | 10GB–100GB | 60–120s |
    | >100GB | ≤120s |

  * Note: 
    * 16/32/64/128 MB: kích thước part tối thiểu cho từng loại file size, để đảm bảo tốc độ và sự ổn định.
    * FileSize: dung lượng file, đơn vị MB
    * 10.000: số lượng tối đa part 1 object, do store minio và s3 quy định
    * Multipart upload cho file lớn >100MB. Mỗi part >5MB trừ part cuối. Tối đa 5BG/part. Part size 16/32/64MB. Tối đa 10.000 part/file. File size tối đa 5TB
  
  * VD: 
    * 500MB. Công thức: max(16MB, ceil(500 / 10_000)) ~ < 16MB tiêu chuẩn -> lấy 16MB => 31,25part
    * 100GB -> 102.400MB. Công thức: max(64MB, ceil(102,400 / 10.000)) ~ 10 < tiêu chuẩn 64MB -> lấy 64MB => 1.600part

### Flow

- Client
  + Có thể dùng 1 request test để đánh giá tốc độ xử lý sau đó chọn giải pháp phù hợp.
  + Tính toán thời gian upload dự kiến để set ttl cho presigned url. Ví dụ: 100MB/s, file 10GB -> 100s -> set ttl 120s


================
* `Flow upload file nhẹ`
  * Minio: Tạo serect key để cho phép hệ thống connect tới bảo mật. Tạo presignUrl tạm thời sau này.

  * Client: 
    * Validate: Tên, loại file, dung lượng file min max, min max tên file,...
    * Kiểm tra nếu file size < 100MB thì thực hiện tiếp
    * Call api để setup presign url upload lên s3 tạm thời. Cho phép bất kỳ put object. Payload request chứa thông tin của file để chỉ định path lưu trữ (uuid làm tên của object, nó sẽ được lưu vào bucket temp)

  * Back-end:
    * Nhận request validate
    * Tạo presign url upload lên bucket temp. Set time life là 5p
    * Trả về response chứa presign url cho client

  * Client:
    * Nhận response nếu thất bại thì thông báo lỗi cho user
    * Nhận response nếu thành công thì call presign url để upload file lên minio, với payload chứa file dưới dạng blob
  
  * Minio:
    * Nhận request upload file, với payload chứa file dưới dạng blob
    * Upload file lên minio
    * Trả về response cho client

  * Client:
    * Nhận response nếu thất bại thì thông báo lỗi cho user
    * Nhận response nếu thành công thì call api để xử lý dữ liệu
    
  * Back-end:
    * Validate thông tin
    * Store thông tin
    * Move file mới upload từ bucket temp vào trong bucket chính. Tìm file trong bucket temp theo thông tin file lấy từ payload. Cập nhật thông tin file vào database
    * Return về kết quả cho client

  * Client:
    * Nếu thất bại thì thông báo lỗi cho user
    * Nếu thành công thì hiển thị thông báo kết quả upload cho user. Quá trình upload hiển thị process, pause, resume, cancel, retry.

* `Flow upload file nặng`
  * Minio: Tạo serect key để cho phép hệ thống connect tới bảo mật. Tạo presignUrl tạm thời sau này. Setup lifecycle AbortIncompleteMultipartUpload ở bucket temp, để thực hiện clean part  đã CreateMultipartUpload khi bị lỗi or chưa commit complete, sau 24H.

  * Client: 
    * Validate: Tên, loại file, dung lượng file min max, min max tên file,...
    * Kiểm tra nếu file size > 100MB thì thực hiện tiếp
    * Call api với payload: file name, file size, file type. Không gửi payload part.

  * Back-end:
    * Verify request, check quyền bằng middleware
    * Validate: Các thông tin request từ client
    * Lấy thông tin file từ payload
    * Tính toán số lượng part song song theo công thức `Công thức tính số lượng part song song`, mặc định là 6. Tính toán số lượng part theo công thức `Công thức tính toán part size`. Tính toán presigned url cho mỗi part theo công thức `Công thức tính TTL cho presigned url`
    * Generate UUID làm tên object để chỉ định tên lưu trữ trong store
    * Call CreateMultipartUpload minio với object id là uuid đã generate để tạo uploadID, mục đích tạo khoảng trống upload trong bucket temp. Dùng để chứa các part file. Dùng cho upload part, Resume, Complete, Abort.
    * Xử lý dữ liệu: 
    * Trả về response chứa: UploadID, objectID, partSize, partNumber, ttl, presignedUrl của từng part.
    * Vì browser giới hạn số lượng request đồng thời, nên số lượng part nhiều hơn giới hạn, thì sẽ cần chia ra upload theo nhiều đợt. Nếu các part phía sau có thời gian presined URL ngắn hơn thời gian upload của các part phía trước, thì ttl của part đó sẽ hết hạn và không được upload do lỗi. Vì vậy, các part được chia thành các đợt, mỗi đợt có số lượng part bằng giới hạn request đồng thời của browser. Mỗi luồng sẽ có thời gian xử lý hoàn tất khác nhau, nên luồng nào xong trước thì thực hiện lấy presigned mới và thực hiện upload part mới.
    mỗi part là 1 request độc lập. Không thể dùng 1 presigned url để upload nhiều part hoặc tạo nhiều presigned url cho cùng 1 part. 1 part = 1 presign Url. Vì để tránh 1 presigned url bị leak ra ngoài và gây ra vấn đề ghi vô hạn dữ liệu. Do browser giới hạn 6-15 connection/domain, nếu quá nhiều sẽ dễ throttle network, tăng latency -> để tầm khoảng 6-8 connection song song khi upload. Để tránh mạng kém, số lượng quá tải khiến xử lý chậm, presignUrl ttl ngắn sẽ hết hạn trước khi xử lý hết
    * Response về thông tin upload part cho client

  * Client:
    * Nhận response: Nếu có lỗi thì thông báo lỗi cho user, nếu thành công thì tiếp tục
    * Cắt file thành các part (byte-range), mỗi part có kích thước bằng nhau (trừ part cuối cùng), mỗi part có thông tin partNumber và partSize. Hệ thống này dùng ở web, dùng Blob.slice để cắt file,. Logic cắt part lấy thông tin từ response
    * Tạo metadata các part: part number, part size, etag, presigned url, số lần upload fail.
    * Tạo worker để xử lý upload part song song, giới hạn số lượng worker bằng giới hạn request đồng thời của browser. Đặt là 6. Tương đương upload 6 part song song
      * Kiểm tra part đó có presigned url không ? nếu có tiếp tục upload. Nếu không thì call api lấy presigned url, kết quả lỗi thì thông báo cho user, thành công thì xử lý tiếp.
      * Gửi request presigned url upload part từ medatadata của part đó, đính kèm part file vào body request. URL presigned http...&partNumber=x&uploadId=abc123. Đính kèm content-type vào header request nếu không sẽ bị minio reject. API tạo 1 api riêng chỉ để tạo presigned url upload part.
      * Nhận response
        * Nếu thành công đánh dấu kết quả, cập nhật etag từ header response và tiếp tục.
        * Nếu lỗi mạng/timeout/5xx thì kiểm tra, nếu part đó có upload fail đến lần thứ 3 thì dừng lại và thông báo lỗi cho user, còn không thì cập nhật tăng số lần upload fail của part đó và thử lại, chiến lược thử lại làm theo công thức `Công thức tính toán thời gian thử lại`. Để mong đợi mạng hoạt động ổn định trở lại. Nếu lỗi url 403 thì thông báo lỗi cho user và kết thúc.
      * Sử dụng sliding window, 1 slot hoàn thành, đánh dấu part done, lấy part tiếp theo trong pending queue đẩy part đó vào slot trống.
    * Khi hoàn tất upload tất cả các part thì tạo payload chứa: object id, upload id, part number, part size, etag. Gọi api với payload này.
    * Sử dụng process bar với công thức `Tính toán tốc độ upload để hiển thị cho user`. Hiển thị process, pause, resume, cancel, retry.

  * Back-end:
    * Validate: Payload gửi lên
    * Gọi CompleteMultipartUpload (không cần presigned URL) minio, với payload là payload của client gửi, để commit tất cả các part lại thành 1 file hoàn chỉnh.
    * Call service để scan virus file vừa upload xong. Nếu file bị nhiễm virus thì thông báo lỗi cho user và xóa file. `Cái này tương lai xử lý sau`.
    * Response về kết quả cho client.
  
  * Client:
    * Nhận response từ back-end. Nếu lỗi thì thông báo lỗi cho user, nếu thành công thì thông báo cho user đã sẵn sàng để submit upload.
    * Khi user submit upload, thì client gọi api submit upload. Call api gửi thông tin metadata của file.

  * Back-end:
    * Xử lý dữ liệu, store lại thông tin, lưu trữ trạng thái file là processing
    * Kích hoạt job move file mới upload từ bucket temp vào bucket chính official.
    * Response về kết quả cho client. Trong đó có room id để user theo dỗi kết quả upload file format dạng uuid_userId_mediaId (vì user có thể upload nhiều file nặng khác nhau)

  * Worker execute job
    * Thực hiện kiểm tra tồn tại của object đó trong bucket temp có tồn tại hay không ? nếu có thưc hiện lệnh move object từ bucket temp -> official, Storage tương ứng sẽ thực hiện move tùy theo kích thước file, nếu kích thước lớn nó sẽ chia thành nhiều part nhỏ để move qua giống như cách upload. Nhưng nó thực hiện nội bộ bên trong chính nó mình không cần can thiệp. khi thực hiện xong nó trả kết quả về.
    * Quá trình chia nhỏ và upload part bị lỗi sẽ thực hiện retry chiến lược 5 lần, fail là kết thúc.
    * Nếu thành công thì thực hiện delete object cũ (vì trong quá trình upload có thể lỗi, cần copy qua, xong rồi xóa đi thì đỡ lỗi).
    * Cập nhật trạng thái upload file là complete or fail vào record trong db
    * Reverb sẽ thực hiện broatcast event kết quả thực hiện đển room id tương ứng với trạng thái file đã thực hiện

  * Client:
    * Nhận response và thực hiện thông báo cho user rằng quá trình đang diễn ra, sẽ thông báo kết quả sau
    * Sau đó lấy room id từ response, thực hiện call wss đăng ký join room để nhận thông báo kết quả file
    * Call api list với id media vừa upload, check column status coi nếu khác processing thì thực hiện thông báo kết quả luôn, sau đó call wss rời room id tương ứng. Vì một số trường hợp file nhỏ job xử lý nhanh tức thì, user call wss kết nối chậm hơn tốc độ xử lý job nên bị miss thông báo.
    * Nếu không thì đợi job broadcast event -> reverb wss service receive -> send to client subscribing room id -> hiển thị thông báo kêt quả -> call wss rời room id tương ứng

Note: 
* Trường hợp upload dở dang mà user reload, close tab, close browser, sleep, turn off,... thì sẽ không hoàn thành upload, các part đã upload sẽ không được merge thành file hoàn chỉnh. Nó sẽ được dọn dẹp bằng lifecycle của minio.

* Kiểm tra web server (nginx, apache, caddy, ...) dùng giao thức HTTP version bao nhiêu để chọn giải pháp xử lý request đồng thời theo công thức `Tính toán số lượng part song song`. Nhưng cũng cần cân nhắc đến khả năng xử lý của client, trình duyệt, thiết bị, đường truyền, ... để chọn giải pháp phù hợp.


#########################################################################################
#########################################################################################
#########################################################################################

# ý nghĩa & nguyên lý hoạt động của một số công nghệ

* Băng thông: Năng lực xử lý tối đa của đường truyền.
  
  * EG: 100Mbps (100 mega bit per second) ~ 12.5 MB. Không dành riêng cho 1 request mà nó là tổng tài nguyên traffic.
    * Thời gian = dung lượng request / tốc độ
  
  * EG: dung lượng request 10Mb, tốc độ xử lý 10Mbps -> thời gian xử lý 1s. Header/tls/metadat rất nhỏ so với body nên có thể bỏ qua.
	
  * Cả 2 request chạy song song, băng thông chia kiểu gì không biết cảm giác như
  được chia đều, request xong trước giải phóng băng thông, băng còn dư sẽ sử dụng cho request đang thực hiện. 
  
  * Trường hợp đang có request chạy mà có request mới vào, thì request cũ vẫn chiếm phần lớn băng thông, request mới từ từ tính toán băng thông lại, nhưng không chia đều ngay. Rồi dần dần chúng mới lại. giống như chia đều băng thông.

* Tốc độ mạng: Tốc độ thực tế của đường truyền.

* Đơn vị đo lường file size:
  * 1 Byte (B) = 8 Bits
  * 1 KB (Kilobyte) = 1.024 Bytes
  * 1 MB (Megabyte) = 1.024 KB
  * 1 GB (Gigabyte) = 1.024 MB
  * 1 TB (Terabyte) = 1.024 GB
  * 1 PB (Petabyte) = 1.024 TB
  EG: MB (megabyte) = Mb (megabit) * 8

* Tốc độ mạng:
  * 4G (20-100 Mbps)
  * 5G (187-393 Mbps)
  * 1 MB (megabyte) = 8Mb (megabit)

* Job: là công việc được lập trình sẵn để thực hiện logic

* Queue: hàng đợi thứ tự, ưu tiên, delay thực hiện job

* Worker: Thực hiện công việc, luôn chạy sẵn, nó sẽ thực hiện các công việc của queue, thực hiện đồng thời or retry. Báo cáo kết quả thực hiện, phát event kết quả thực hiện

* Reverb: Broadcast event kết quả theo room id

* Web worker: là một script chạy ở nền tảng background, chạy độc lập và song song với UI thread, không ảnh hưởng đến UI thread.
Nó không ảnh hưởng đến hiệu suất giao diện. Hạn chế là nó chạy riêng biệt nên không có quyền truy cập trực tiếp vào DOM.
Nó hoạt động đa luồng, triển khai thông qua việc nhận tin nhắn và gửi tin nhắn.

#########################################################################################
#########################################################################################
#########################################################################################

# Steaming

* Nguyên tắc: Khi stream không gửi yêu cầu và nhận response hẳn 1 file để stream, vì nó có dung lượng lớn, cản trở băng thông, tốc độ xử lý, dung lượng lưu trữ. Ảnh hưởng trực tiếp đến stream realtime. Nên video được encode và chia thành nhiều sements nhỏ (vd 10s/segment) để stream. Trình phát sẽ tài dần từng đoạn.

* Lựa chọn chất lượng video: Đầu tiên là độ phân giải nguyên bản của video đó. Sau đó là bitrate, fps, codec, ...

* Adaptive Bitrate: Tự động điều chỉnh chất lượng video dựa trên tốc độ mạng, CPU, GPU, ...

* CDN caching video: Cache video trên CDN để giảm tải cho server.

* FFmpeg transcoding: Framework multimedia, nó là mã nguồn mở, dùng để xử lý decode, encode, transcode, mux, demux, stream, filter, và play audio/video và hầu hết mọi định dạng dữ liệu được tạo ra. 1 vài ví dụ: chuyển đổi định dạng từ video mov -> mp4, video -> file nhạc mp3. Nén dung lượng giảm kích thước file. Cắt ghép video, thêm hiệu ứng, watermark. Trích xuất âm thanh từ video. Live streaming. Đổi đuôi file,... Sử dụng FFmpeg trực tiếp khó và nhiều rủi do. Nên sử dụng công cụ chuyên dụng.
Riêng việc live stream, nó đóng vai trò :
  + Nén dữ liệu: Mã hóa video sang H.264, H.265, ... 
  + Chuyển mã: Chuyển video gốc sang nhiều bản với chất lượng khác nhau
  + Chia đoạn (segment): Cắt các phiên bản đó thành các đoạn nhỏ (vd 10s/segment)
  + Tạo file chỉ mục (Manifest): Tạo ra file .m3u8 (cho HLS) hoặc .mpd (cho DASH). Bên trong các file đó là một tập hợp danh sách các file .ts (segment cho HLS) hoặc .m4s (segment cho DASH) cùng playlist. Đây là "bản đồ" or file menu, để trình phát biết cần lấy đoạn video nào tiếp theo.

* HLS (m3u8, ts), DASH (mpd): Trước đây mỗi khi muốn stream video, client phải tải toàn bộ file video về, sau đó trình phát mới phát. Điều này gây lãng phí băng thông, tốc độ xử lý, dung lượng lưu trữ. Ảnh hưởng trực tiếp đến stream realtime. Việc này rất bất tiện.
Do đó HLS (apple) và DASH (mpeg) ra đời để giải quyết vấn đề này. Chúng là 1 dạng giao thức phân phối video. Chúng thực hiện điều khiển các đoạn nhỏ (segment) của video. Điều khiển thay đổi chất lượng video khác nhau, đã có sẵn. Nếu mạng yếu, trình phát tự động sẽ giảm chất lượng video xuống để quá trình stream giảm tối đa tiến độ stream. Nhờ vậy, user có thể load video và xem nhanh chóng, cảm giác như liên tục mà không cần phải tải hết toàn bộ video về một lần. Sau khi hoàn tất nó trả về luồng video qua HLS hoặc DASH.

* Player: Trình phát video, có các chức năng điều khiển video một cách trực quan. Các action sẽ thực hiện serve qua HTTP. Player sẽ đọc file manifest và tải các segment tương ứng thông qua giao thức HLS|DASH. Nó sẽ tự xử lý Adaptive bitrate switching, Buffering strategy, Segment fetching, Fallback network.

* VOD: Trong stream video là công nghệ cho phép user xem các nội dung video được lưu trữ sẵn bất cứ lúc nào, thay vì tuân theeo lịch phát sóng cố định. Người dùng có thể điều khiển nội dung video theo ý muốn.

* Các file có đuôi mp4, mp3, webm,... là cái hộp chứa dữ liệu. Browser không quan tâm đuôi file, nó quan tâm codec bên trong có được hỗ trợ hay không.
Vấn đề là không tương thích là mỗi browser hỗ trợ codec khác nhau, OS hardware decode khác nhau, thiết bị có cấu hình CPU GPU RAM khác nhau, tốc độ mạng khác nhau, ...

* Video.js: thư viện javascript để phát video. Nó hỗ trợ nhiều định dạng video, bao gồm HLS và DASH. Nó cũng hỗ trợ nhiều tính năng, bao gồm adaptive bitrate switching, buffering strategy, segment fetching, fallback network. Nó là một player thuần, không có tính năng transcoding. Sử dụng ở front-end.

* Nginx
  * Cấu hình client_max_body_size: giới hạn kích thước request body. Dùng cho path /upload tương ứng, các path khác không bị ảnh hưởng.
  * Cấu hình proxy_buffering, proxy_request_buffering : tắt buffering request body. Dùng cho path /stream tương ứng, các path khác không bị ảnh hưởng.

* Các hướng triển khai:
  * Tự Host: cái này phức tạp và mất nhiều thời gian để cân nhắc nghiên cứu và triển khai sau.
    * Giai đoạn xử lý: User upload file video thí dụ upload file format mp4 lên bucket
    * Giai đoạn xử lý (transcoding)
      * Tự viết worker (node.js, python, go) lắng nghe event khi có file video mới được upload
      * Sử dụng ffmpeg để chuyển đổi file .mp4 thành định dạng HLS(file .m3u8 và mảnh .ts)/DASH(file .mpd và mảnh .m4s)
      * Đẩy tất cả các file đã xử lý ngược lại lưu vào minio, sửa lại path để có thể truy cập stream video
    * Giai đoạn phát (stream)
      * Sử dụng web server hoặc dùng tính năng static website hostting của minio để serve các file .m3u8 và .ts
      * Ở client, sử dụng thư viện video.js, hls.js,... để phát
  * Sử dụng các dịch vụ cloud, platform: cái này thì không có tiền bù lại tiện và nhanh
  * Sử dụng media server: ant media, wowza, red5,... dính bản quyền, khó custom sâu. Cấu hình để chúng lấy file từ minio, tự động convert và stream
  * Webserver + vod: sử dụng nginx-vod-module. Nó là transcoding on-the-fly. nginx sẽ tự động convert video đó thành các định dạng stream và có thể sử dụng giao thức HLS hoặc DASH để stream video. Nó không cần lưu trữ các file đã convert. Tốc độ chậm hơn so với convert lưu trước chỉ việc call để sử dụng.

* Tự host: Phức tạp tốn thời gian, rủi do nhiều => không hiệu quả nếu không chuyên sâu
  * User request stream: browser gửi request với range tương ứng
  * Transcode: Chạy job ffmpeg ngay lúc đó hoặc thực hiện trước để sử dụng
    * Convert codec:
      Video: → H.264
      Audio: → AAC
    * Tạo nhiều bitrate:
      240p / 360p / 720p / 1080p
    * Chia segment:
      2–6 giây / segment
  * Output: Server phải chuẩn bị sẵn các công nghệ đề hỗ trợ trên các platform khác nhau
    * HLS:
      index.m3u8
      chunk_000.ts
    * DASH or HLS + JS:
      manifest.mpd
      segment_001.m4s
  * Lưu vào minio: Chỉ là storage, không quan tâm định dạng, ko biết stream là gì.
  * Client tự chọn protocal phù hợp, không phải server
    Thiết bị|Công nghệ
    Safari | iOS	HLS native
    Chrome | Firefox	hls.js
    Smart TV / Android|DASH / ExoPlayer

* Dùng video html5 với URL connect của minio. File khá lớn 7GB, nó đang sử dụng cơ chế Progressive MP4 Streaming via HTTP Range Requests để stream file. Triển khai cực đơn giản.

|Tiêu chí|Progressive MP4 (HTTP Range)|nginx-vod|
|---|---|---|
|Protocol|hỗ trợ	Chỉ MP4 đơn giản qua Range Requests|DASH, HLS, HDS, MSS adaptive|
|Adaptive Bitrate|Không|Có, multi-bitrate tự động|
|Độ trễ|Thấp (low latency), phù hợp VOD nhanh|Cao hơn do segmenting, nhưng linh hoạt live |
|Lưu trữ|Hiệu quả (1 file duy nhất) |Overhead segment, nhưng cache tốt|
|Seek chính xác|Cao, byte-level |Tốt, nhưng phụ thuộc segment duration|
|Tính năng nâng cao|Cơ bản (clipping thủ công)|Track selection, encryption (DRM, AES), thumbnail, subtitles |
|Phức tạp triển khai|Thấp (chỉ config Nginx add_header Accept-Ranges bytes) |Cao (compile module, config modes: local/remote/mapped) |
|Hiệu suất|Cao cho single stream, ít CPU |Tối ưu với cache (metadata/response), ~26MB/s trên 4-core AWS |
|Use case lý tưởng|VOD đơn giản, low-latency, ít thiết bị |Adaptive streaming, live sim, multi-device, enterprise |


* Lựa chọn triển khai: Sử dụng minio để lưu trữ file, sử dụng nginx-vod-module đóng vai trò streaming server, nó đọc file từ storage (minio) qua giao thức HTTP sau đó nó tự convert on-the-fly thành HLS hoặc DASH để trình duyệt phát được. Sử dụng video.js làm trình phát video ở client.

* Luồng xử lý nghiệp vụ:
  * Step 1: client upload file lên minio thông qua presigned url. **Lưu ý:** lưu file dưới dạng fragmented MP4 or Fast start để nginx-vod đọc nhanh mà không cần tải toàn bộ file về RAM
  * Step 2: BE lưu thông tin metadata của video vào database
  * Step 3: Yêu cầu phát video
    * Client click play, FE không call trực tiếp link minio. Thay vào đó, FE call một URL có cấu trúc đặc biệt tới nginx-vod. Ví dụ: http://nginx-vod/vod/video.mp4/playlist.m3u8
  * Step 4: Xử lý tại nginx-vod 
    * Ánh xạ: Nginx-vod nhận được request, nó sẽ sử dụng chế độ mapped mode. Nó gửi truy vấn nội bộ để xác định xem file video đó đang nằm ở đâu trong minio.
    * Lấy dữ liệu: Nginx kết nối tới minio qua HTTP/s3 để đọc các byte dữ liệu cần thiết của file gốc.
    * Đóng gói: Nginx-vod cắt file .mp4 thành các segement và tạo file danh sách phát ngay trong bộ nhớ.
    * Nginx-vod trả về luồng video qua HLS hoặc DASH cho client.
  * Step 5: Client sử dụng video.js để phát video.
    * Các action stream
      * play: browser gửi request với range từ 0-
      * Pause: Browser tự ngắt TCP
      * Seek, next, back: Browser gửi request với range tương ứng
      * Resume: Browser gửi range tiếp
      * Phóng to|thu nhỏ: css/player thực hiện
      * Chất lượng đồ họa: Player (HLS/DASH)
      * Âm Lượng: browser thực hiện
      * Thời giản phát: browser
      * Speed: Browser
  * Step 6: Tạo một event timeUpdate của video khi chạy định kỳ mỗi 5s, gọi webworker để lưu thời điểm hiện tại của video, id video vào indexDB để tránh block UI. Khi reload or truy cập lại video, kiểm tra video đó trước đây đã xem đến đâu bằng cách kiểm tra và lấy dữ liệu trong indexDB, nếu có thì set thời gian hiện tại của video bằng thời gian đã lưu (range). Event này chỉ chạy khi user xem video, còn không thì clear.

* Cách thức triển khai:
  * Chế độ mapped mode: Tạo file json mô tả vị trí trên minio. nginx-vod sẽ đọc file JSON và biết lấy dữ liệu ở đâu. Giúp link minio gốc không lộ ra ngoài
  * Cấu hình header  & CORS: vì FE và webserver có thể nằm trên các domain|subdomain khác nhau. Nên cần cấu hình CORS để trình phát video đọc được các segment
  * Phân quyền: BE sẽ tạo ra presigned url or token 1 lần để nginx kiểm tra token trước khi bắt đầu đóng gói video từ minio. Đảm bảo bảo mật.
  * Băng thông: Nginx-vod sẽ hỗ trợ adaptive bitrate. Nếu cho nhiều phiên bản chất lượng cùng một 1 video, nginx-vod sẽ tự động chuyển đổi giữa chúng tùy theo tốc độ mạng giữa chúng tùy theo tốc độ mạng của người dùng.
  * CPU: Việc đóng gói tốn CPU của server nginx. Nếu lượng người dùng cực lớn, cần có chiến lược cache các phần đoạn video đã được cắt.
  * Truy cập Minio: 
    * Truy cập nội bộ cấu hình nginx-vod để nó tự thêm header xác thực (access, secret key) để call tới minio. Không cần presigned url. Không cần quản lý refresh url.
    * Tạo presigned url để nginx-vod call tới minio. Cần quản lý refresh url.
  * Lưu trữ dữ liệu xử lý: Nginx-vod đọc 1 lượng nhỏ dữ liệu từ minio vào bộ nhớ đệm (ram), sau đó đóng gói thành định dạng HLS/DASH và đẩy về phía user thông qua kết nối HTTP. Không lưu trữ lại dữ liệu đã xử lý.
  * Xử lý file lớn: Nó yêu cầu minio trả về các byte cụ thể mà nó cần để tạo ra phân đoạn trong video. Do đó 100MB hay 100GB không khác biệt nhiều. Nếu file video được tối ưu tốt đưa moov atom lên đầu, nginx-vod tiêu thụ RAM sẽ hoạt động cực nhanh, cực nhẹ. Vì chỉ đóng gói lại mà không cần giải mã/nén lại luồng, nên CPU tốn rất ít so với việc dùng FFmpeg để convert.
  * Xử lý nhiều request cùng lúc: Nginx-vod hoạt động dựa trên mô hình non-blocking của nginx. Nó có thể xử lý hàng ngàn kết nối đồng thời. Tuy nhiên, mỗi kết nối active sẽ chiếm một lượng RAM nhất định để lưu trữ buffer và metadata của segment. Nếu có quá nhiều request, RAM có thể bị quá tải. Nên cần có cơ chế nginx proxy cache kết hợp với nginx-vod để giảm tải cho nginx-vod.


* Đối với file nặng, mà cần move file từ bucket này qua bucket khác, thì thực hiện server-side Multipart Copy + Parallel Threads + batch job, thêm column đánh dấu trạng thái tình trạng hoàn thành, cập nhật column này là processing. Thông báo cho user "File của bạn đang được xử lý hệ thống. Chúng tôi sẽ thông báo khi file sẵn sàng.", sau đó tắt modal or dialog để cho user tiếp tục thực hiện trên web. Sau đó job sẽ chạy sau đó thực hiện xử lý ngầm, khi move hoàn thành thì thực hiện update lại status là upload completed. Tận dụng sức mạnh tối đa đa luồng, phần cứng của minio. Khi bị fail thì retry lại theo chiến lược thời gian giữa các đợt retry dãn dần ra để tình trạng mạng phục hồi lại. Giới hạn số lần retry là 5 lần. Nếu quá số lần retry thì update fail.

* Nhu cầu từ truy cập dữ liệu từ nhiều máy -> internet
* Nhu cầu cập nhật thông tin và sự phổ biến của nó -> sử dụng trình duyệt -> giao thức kết nối http để gửi và phản hồi dữ liệu.
* Nhu cầu tương tác với dữ liệu trên trình duyệt (như validate form trước khi request) -> javascript.
* Nhu cầu thao tác với dữ liệu trên trình duyệt mượt mà không tải lại toàn bộ page trên browser mỗi khi request -> ajax.
* Nhu cầu gửi nhận dữ liệu 2 chiều kịp thời nhanh chóng thời gian thực thay vì request để lấy dữ liệu mới -> giao thức websocket. 
* Nhu cầu cao hơn về thao tác dữ liệu thời gian thực với độ trễ thấp -> websocket + http/2, webRTC, webtransport.

* KHi một số mô hình, công nghệ, khái niệm mới chứng mình được sự ưu việt thì trình duyệt, ngôn ngữ lập trình, các hệ sinh thái,... sẽ cố gắng lập trình, hấp thụ, kế thừa chúng vào để đáp ứng đầy đủ và đa dạng nhất tất cả các yêu cầu của người dùng để thích nghi và tồn tại. Quá trình diễn ra 2 hướng, 1 là các lớp lõi sẽ tích hợp or hỗ trợ chúng trong các phiên bản cập nhật về sau, 2 là hệ sinh thái như cộng đồng, doanh nghiệp,... sẽ phát triển các thư viện, framework, công cụ,... để hỗ trợ chúng.

* Websocket phức tạp để mở rộng bời vì, khi cần mở rộng có 2 hướng, mở rộng theo chiều dọc nghĩa là mở rộng cấu hình xử lý thường tốn kém, mở rộng chiều ngang thì độ phức tạp tăng lên vì bản chất của websocket kết nối liên tục và nó chỉ biết bản thân và máy khách để trao đổi thông tin, khi mở rộng cần thêm các phương thức mở rộng mà không mất kết nối, chia sẽ dữ liệu đồng thời trên nhiều máy khách, cần thêm các thành phần trung gian để quản lý, điều phối, định tuyến, cân bằng tải, lưu trữ, xử lý,...

* Websocket: là application-layer protocol, chạy trên TCP, không phải thay thế TCP. Giao thức truyền tải dữ liệu cho phép thiết lập một kênh liên lạc 2 chiều, duy trì liên tục giữa trình duyệt và máy chủ qua 1 kết nối TCP duy nhất. Khác với giao thức HTTP truyền thống, client hỏi và nhận phản hồi từ server, websocket cho phép cả 2 đều có thể chủ động gửi thông tin cho bên kia bất kỳ lúc nào sau khi kết nối.

  * Định nghĩa các thành phần:
    * Client: Là 1 socket id duy nhất, mỗi client tự lưu trữ room id trong bộ nhớ để xác định gửi, nhận, rời room tương ứng. Mỗi client có thể đăng ký nhiều server websocket khác nhau, khi gửi tin nhắn cần chọn server websocket id để gửi, nội dung gửi cần có room id để gửi đúng room, nội dung message là thông tin cần gửi.
    * Room: Là không gian chung cho nhiều client có thể theo dõi message của nhau. Ban đầu thiết lập kết nối mới, nó sẽ thực hiện tạo các đường dẫn đến client tương ứng và kết nối liên tục. KHi có tin nhắn chúng sẽ gửi tin nhắn đó đến các client trong room, trừ client gửi, khác với giao thức HTTP khi gửi dữ liệu cần có địa chỉ IP server. Mặc định các server websocket sẽ tự clear các room khi không có client nào.
    * server Websocket: là máy chủ quản lý danh sách các room và socket id trong ram. CLient join room thì nó sẽ lưu trữ socket id của client đó vào trong room đó. Khi client rời room nó sẽ xóa socket id khỏi room đó. 

  * Hệ thống phân luồng
    * Public room: Tất cả user đều nhận được thông báo
    * Private room: Chỉ user có trong room mới nhận được thông báo
    * Presence room: Giống private nhưng biết thêm ai đang connect (online)

  * Hệ thống xác thực
    * Reverb có cơ chế verify token để xác thực khi client gửi request có đính kèm token trong authorization header. Nó có sẵn nên không cần thực hiện thêm
    * BE sẽ xác thực request user thông qua token có thể để trong auth or cookie, sau khi xác thực thành công sẽ kiểm tra quyền truy cập vào room tương ứng. Nếu tất cả hợp lệ thì mới thực hiện return về token được generate từ secret key, key này với reverb là 1. Nếu không thì return lỗi.
    * Client khi cần gửi tin nhắn cần trỏ đúng địa chỉ IP server websocket, FE cần cài thêm thư viện như laravel-echo or pusher-js để thiết lập đường dẫn wss đến reveb. Reveb đã thực hiện tích hợp verify token để xác thực client có quyền truy cập vào wss hay không, việc cần thực hiện là tạo secrect key khớp với một hệ thống xác thực khác, ở đây là BE sẽ xác thực và tạo token với secrect giống reverb, client sẽ sử dụng token này để xác thực khi kết nối wss.

  * Cách thức hoạt động:
    * Handshake: client gửi request http get đặc biệt tới server với yêu cầu kết nối websocket.
    * Open connection: Nếu server đồng ý, kết nối được thiết lập. Lúc này, giao thức chuyển từ HTTP -> websocket.
    * Data trasnfer: Cả 2 đều có thể gửi dữ liệu cho nhau theo frame. Cực nhẹ mà ko cần gửi lại các thông tin header rườm rà.
    * Close: 1 trong 2 có thể đóng kết nối bất kỳ khi nào.


* FLow
  * Client: khi cần theo dõi thông báo từ channel thông báo kết quả upload file nặng đang chạy ngầm. Sau đó thực hiện gửi request đến server websocket khởi tạo yêu cầu kết nối.
  * Nginx: Sẽ proxy request đến server websocket.
  * Reverb: Sẽ xác nhận yêu cầu kết nối, nếu hợp lệ thì sẽ trả về kết nối thành công, ngược lại sẽ trả về lỗi. Không xác thực. Lúc này như ở ngoài sảnh khách sạn ai cũng được vào nhưng chưa được xác thực phòng.
  * Client: Thông qua thư viện laravel echo để request http post tới laravel để xác thực kết nối channel, lúc này param sẽ đính kèm channel cần kết nối. Thực hiện tạo param channel name là admin id + channel name (upload file nặng). Trong request mặc định sẽ tự đính kèm cookie (chứa access token). Có nghĩa request này sẽ thực hiện sau khi login thành công.
  * Nginx: Sẽ proxy request đến laravel
  * laravel: Sẽ thực hiện logic xác thực adminMiddlware check cookie có hợp lệ không sau đó next request. Lúc này broadcast middleware sẽ thực hiện tiếp kiểm tra lấy admin id trong param request đối chiếu với admin id được verify từ cookie chứa access token dạng jwt. Nếu khớp thì thực hiện tạo chữ ký mã hóa trả về cho client. Chữ ký mã hóa này có key là secret key của reverb. Response trả về sẽ dạng một chuỗi json như {"auth": "a1b2c3d4e5..."}.
  * Client: Sử dụng chữ ký mã hóa này để xác thực kết nối channel với server websocket. Thông qua laravel-echo đóng gói lại thành wss frame.
  * Reverb: Sẽ xác nhận yêu cầu kết nối, giải mã chữ ký mã hóa, nếu hợp lệ thì sẽ trả về kết nối thành công, ngược lại sẽ trả về lỗi. Không xác thực.
  * Job laravel: Khi tạo job thực hiện hoàn tất, cần lưu trũ admin id (lấy trong cookie verify decode token lúc request kích hoạt job này), khi quá trình xử lý job hoàn tất thì nó thực hiện tạo channel với format tương ứng nó sẽ mã hóa các thông tin thành json và push vào kênh nội bộ redis.
  * Reverb: Subcriber kênh nội bộ redis từ trước, ngay sau khi có tin nhắn mới. Nó sẽ đọc nội dung gói tin json đó, tìm kiếm channel name tương ứng đang tồn tại, nếu tồn tại thì nó sẽ gửi tin nhắn đến các socket id trong channel tương ứng.
  * Client: Nhận tin nhắn và thực hiện hiển thị thông báo kết quả job cho người dùng cuối. Thực hiện close connection hoặc xóa room ngay khi nhận tin nhắn.
  * Laravel job: Tạo job chạy định kỳ để tự động clear các record có upload status là inprogress với update time >24H so với hiện tại. Để tránh rác dữ liệu, còn ở storage thì có lifecycle để tự động clear rồi.

  * Note: Nếu quá trình xử lý mà client rời đi thì khi quay lại client sẽ phải tải dữ liệu mới lúc này không cần thông báo message miss nữa. Khi cần thực hiện ứng dụng chatapp thì mới cần đồng bộ message
