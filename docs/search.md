- Search một chức năng giúp người sử dụng nhanh chóng lọc ra các thông tin mà họ cần tìm kiếm dựa trên các từ khóa hoặc cụm từ khóa mà họ nhập vào ô tìm kiếm.

- Hiện tại search rất phổ biến và sử dụng hầu hết trong các ứng dụng, khi chúng ngày càng phình to. Do đó việc lọc dữ liệu là thành phần quan trọng trong trải nghiệm sử dụng. Có nhiều công cụ search hiện đại và tiện dụng khiến người dùng trở nên ngày càng được nuông chiều, thí dụ họ có thể nhập sai chính tả, mô tả không rõ, mô tả liên quan không cần chính xác, sự đơn giản hóa khi mọi thứ trước mắt người sử dụng chỉ có 1 form search duy nhất, họ có thể nhanh chóng tìm kiếm chính xác thông tin mà họ cần.

- Ý tưởng search kiểu cổ điển:
  + Quét qua từng dòng dữ liệu và so sánh giống từ trước đến or từ sau đến (%like%), cách này đơn giản nhưng kém thông minh. Nó thực hiện cần nhiều tài nguyên -> rất chậm
  + Tạo form đơn giản và form chi tiết chứa các input là các column tương ứng của chúng trong table của DB. Cách này đơn giản, rõ ràng dễ thực hiện nhưng kém linh hoạt, khi số lượng column quan trọng tăng lên thì form search cũng phình to, khó trực quan cho người dùng mới.

- Ý tưởng search theo kiểu mới (take care user tận giường, dâng đến tận mồm)
  + Sử dụng cấu trúc dữ liệu dạng inverted index để lưu trữ thông tin của các column quan trọng. Khi người dùng nhập vào ô tìm kiếm, hệ thống sẽ tìm kiếm trong inverted index và trả về các kết quả tương ứng. Cụ thể nó tách nội dung thành các từ đơn, loại bỏ các từ vô nghĩa (và, thì, là, mà, nhé,...) đưa chúng về từ gốc. Sau đó lập bảng mới vd từ khóa x ở bài viết y. khi search nó sẽ tra cứu từ bảng này để tìm kiếm, thay vì quét toàn bộ dữ liệu.
  + Xếp hạng tìm kiếm (ranking): Đây là linh hồn của search. Ví dụ dùng thuật toán TF-IDF or BM25 chấm điểm mức độ liên quan của từ khóa với nội dung. Sau đó trả về các kết quả tương ứng với mức độ liên quan của từ khóa với nội dung.
  + 

- Hạng mục đánh giá kết quả search
  + Độ liên quan: kết quả có đúng ý định user không ?
  + Độ sai lệch, chấp nhận sai xót: chấp nhận mức độ sai lệch chính tả
  + Trạng thái rỗng: Gợi ý cho user mỗi kí tự họ tìm kiếm hoặc thay thế chúng bằng từ khóa liên quan, phổ biến,... cần có autocomplete/typeahead
  + Tốc độ: kết quả có nhanh không ? yêu cầu dưới 50ms để tạo cảm giác realtime (hệ thống lớn)
  + Độ chính xác: kết quả có đúng với dữ liệu không ?
  + Độ đầy đủ: kết quả có đầy đủ không ?
  + Độ mới: kết quả có mới không ?
  + Độ phổ biến: kết quả có phổ biến không ?
  + Độ tin cậy: kết quả có tin cậy không ?
  + Độ dễ hiểu: kết quả có dễ hiểu không ?
  + Độ dễ sử dụng: kết quả có dễ sử dụng không ?
  + Độ dễ truy cập: kết quả có dễ truy cập không ?
  + Độ dễ tìm kiếm: kết quả có dễ tìm kiếm không ?
  + Độ dễ chia sẻ: kết quả có dễ chia sẻ không ?
  + Độ dễ lưu trữ: kết quả có dễ lưu trữ không ?
  + Độ dễ xóa: kết quả có dễ xóa không ?
  + Độ dễ sửa: kết quả có dễ sửa không ?
  + Độ dễ sao chép: kết quả có dễ sao chép không ?
  + Độ dễ dán: kết quả có dễ dán không ?
  + Độ dễ in: kết quả có dễ in không ?
  + Độ dễ xuất: kết quả có dễ xuất không ?

- Issue: để phát triển từng tính năng đơn lẻ thì rất nhiều, vd để có chức năng trending/popular cần có cả module thống kê, phân tích về chúng và chấm điểm đánh giá chúng.

- Thay vì phát minh lại bánh xe, triển khai chúng. Nếu muốn nhanh chóng thực hiện mà không đi sâu cụ thể về chúng, thì sử dụng lại.
Hiện tại có 2 hướng sử dụng: 1 là thông qua dịch vụ cloude, 2 tự host bằng các open-source. Chọn 2 vì nó free và để học thêm.
- Các open-source đã bao quát các vấn đề như: sai chính tả, search nhanh như thời gian thực, xử lý ngôn ngữ tự nhiên, các tính toán kết quả siêu nhanh.