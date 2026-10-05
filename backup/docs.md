+ Quy trình

+ Điều hướng: Sử dụng reverse proxy của webserver hoặc CDN/load balancer chỉ cần thay đổi config để điều hướng traffic sang 1 page html tĩnh siêu nhẹ có sẵn. Trả về mã code 503 theo quy định của các browser các bot tìm kiếm biết rằng: hệ thống đang bảo trì, giúp seo không bị tụt hạng (có vẻ browser đánh giá tần xuất lỗi request -> trải nghiệm người dùng). Khi khôi phục hoàn thành tắt config đi để mọi thứ hoạt động như cũ.

+ Đồng bộ: Quá trình bảo trì user ở page thông báo bảo trì và các module logic đã bị tắt do đó data sẽ không bị thay đổi bởi yếu tố bên ngoài trong thời gian này. Vấn đề đăng nhập thì chúng được giữ trạng thái ở cookie và redis, khi bảo trì 2 thành phần này không bị refresh lại nên user không bị mất trạng thái login. Trừ khi các thành phần này bị ảnh hưởng bởi đợt bảo trì cần rebuild lại cache thì mới bị ảnh hưởng cần login lại.

+ Chiến lược backup/restore: Nhìn chung sẽ phải chia ra nhiều trường hợp và các kịch bản trong các trường hợp đó. Vấn đề đồng bộ toàn bộ sẽ đơn giản hóa mọi thứ bằng cách khôi phục cùng lúc tất cả, nhưng điều này có các đặc điểm hạn chế về thời gian khôi phục chậm, tiêu tốn nhiều tài nguyên,... chỉ sử dụng chúng khi cháy nhà như hệ thống bị kiểm soát mã hóa toàn bộ,... Vấn đề khôi phục từng phần kiểu như phần nào cần khôi phục hoặc các phần liên quan cần cập nhật lại thì mới tiến hành khôi phục chúng, nhưng điều này sẽ phức tạp hơn và cần phải tính toán nhiều hơn. Vấn đề về cache, trừ khi nó hữu dụng và không bị ảnh hưởng bởi đợt cập nhật nếu không nó cũng sẽ bị clear và rebuild.

+ Các vấn đề khác:
  + Thời gian dự kiến bảo trì hay nâng cấp hệ thống là dự kiến + buffer
  + Trong quá trình bảo trì nếu gặp thất bại và không thể fix nhanh trong thời gian dự kiến, sẽ chuẩn bị một kịch bản rollback lại bằng CI/CD. Để hệ thống như cũ cho người dùng sử dụng, tránh gián đoạn quá lâu. Vấn đề kia sẽ được xử lý ở phạm vi môi trường stg/dev/trial.
  + 
