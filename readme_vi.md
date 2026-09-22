> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./readme.md)

# Plugin Product Flash Sale cho S-Cart (GP247)

## Giới thiệu
Tài liệu này hướng dẫn cài đặt và sử dụng plugin **Product Flash Sale** — công cụ bán hàng theo khung giờ, có giới hạn số suất ("chỉ 20 suất, trong 3 tiếng"). Tài liệu dành cho chủ cửa hàng và người quản trị site GP247, không yêu cầu biết lập trình. Đọc xong, bạn tự tạo được một đợt Flash Sale, gắn nó lên trang chủ và hiểu vì sao hệ thống đôi khi từ chối một thao tác.

## Flash Sale khác gì "Khuyến mãi" có sẵn?

Cửa hàng GP247 vốn đã có khuyến mãi: một mức giá áp dụng trong một khoảng ngày, thường kéo dài hàng tuần. Vì thế khối "Promotion products" mặc định của cửa hàng **không** có đồng hồ đếm ngược — đếm ngược cho một chương trình dài hai tháng là tín hiệu khan hiếm giả.

Flash Sale là một lời hứa đếm được: **chỉ N suất, trong khung giờ này**. Plugin thêm đúng hai thứ vào khuyến mãi có sẵn: **số suất của đợt** và **thứ tự hiển thị**. Nhờ vậy đồng hồ đếm ngược và thanh "đã bán / còn lại" ở đây là thật.

## Yêu cầu trước khi cài

- GP247 core phiên bản **3.0** trở lên.
- Đã cài gói `gp247/shop` (phần bán hàng). Plugin không chạy nếu thiếu gói này.

## Cài đặt

1. Đặt thư mục mã nguồn plugin vào đúng vị trí trên site của bạn:

   ```
   app/GP247/Plugins/ProductFlashSale
   ```

2. Đăng nhập trang quản trị, vào menu **Extensions / Plugins** (Quản lý Plugin).

3. Tìm dòng **Product Flash Sale**, nhấn **Install**, sau đó nhấn **Enable**.

   Nếu thành công, hệ thống hiện thông báo cài đặt thành công. Nếu báo *không tương thích*, gần như chắc chắn site đang chạy core cũ hơn 3.0 — hãy cập nhật core trước.

4. Hệ thống tự làm ba việc: tạo bảng dữ liệu `shop_product_flash` (nếu chưa có), thêm mục menu dưới nhóm **Catalog** (Danh mục), và **đặt sẵn dải Flash Sale xuống cuối trang chủ** của các cửa hàng chưa có khối này (một dòng trong màn **Layout block**, đang bật). Khối `product_flash_sale` cũng xuất hiện sẵn trong ô chọn của màn Layout block — plugin **đăng ký** khối với hệ thống, không copy file nào vào thư mục template, nên khối chạy với mọi template và biến mất sạch khi bạn gỡ plugin.

   Nếu bạn xoá hoặc chuyển dải đi chỗ khác, **cập nhật plugin sau này sẽ không đặt lại** — vị trí hiển thị là quyết định của bạn, plugin chỉ gợi ý một lần lúc cài.

5. Nếu site của bạn bật cache, xoá cache một lần:

   ```
   php artisan optimize:clear
   ```

6. Vào menu **Product Flash Sale** vừa xuất hiện. Nếu thấy màn hình hai cột (bên trái là biểu mẫu, bên phải là danh sách trống) thì plugin đã sẵn sàng.

> **Cài lại hoặc cập nhật plugin không làm mất dữ liệu.** Bảng chỉ được tạo khi chưa có, mục menu chỉ thêm khi còn thiếu. Chỉ khi bạn bấm **Uninstall** thì bảng mới bị xoá.

## Tạo một đợt Flash Sale

1. Vào menu **Product Flash Sale**.

2. Ở cột trái, ô **Chọn sản phẩm**: gõ vài chữ trong tên sản phẩm rồi chọn từ danh sách gợi ý.

3. Nhập **Số lượng bán** — đây là số suất tối đa của đợt, ví dụ `20`.

4. Nhập **Giá** khuyến mãi. Giá nhập theo **tiền cơ sở** của site (dòng chữ nhỏ dưới ô sẽ nhắc bạn đang nhập theo đơn vị nào).

5. Chọn **Bắt đầu** và **Kết thúc**. Lịch có cả giờ và phút — đây là điểm khác với khuyến mãi thường, bạn đặt được đợt chạy từ 20:00 tới 23:00 cùng ngày.

6. Nhập **Sắp xếp** (số nhỏ hiển thị trước trên trang bán hàng) và bật công tắc **Trạng thái**.

7. Nhấn **Lưu**. Nếu thành công, đợt bán hiện ngay ở danh sách bên phải với thanh tiến độ `0 / 20`.

8. Mở trang Flash Sale trên site để kiểm tra (xem địa chỉ ở mục dưới). Nếu đang trong khung giờ, bạn sẽ thấy sản phẩm kèm đồng hồ đếm ngược.

Muốn sửa: nhấn biểu tượng bút chì ở dòng tương ứng, sửa rồi **Lưu**. Muốn dừng đợt sớm: tắt công tắc **Trạng thái**, hoặc xoá hẳn dòng đó bằng biểu tượng thùng rác (xoá dòng cũng gỡ luôn mức giá khuyến mãi khỏi sản phẩm).

## Trang Flash Sale trên site

Địa chỉ trang danh sách:

```
https://<tên-miền-của-bạn>/plugin/product_flash_sale/index
```

Nếu site bật đường dẫn đa ngôn ngữ (`GP247_SEO_LANG`), địa chỉ có thêm mã ngôn ngữ, ví dụ `/vi/plugin/product_flash_sale/index`.

Trang này cũng là một **loại trang** trong màn **Layout block** của quản trị, nên bạn gắn thêm banner, khối HTML hay trang tĩnh vào đó như mọi trang khác. Khi đang có đợt chạy, trang được khai báo vào `sitemap.xml` để công cụ tìm kiếm biết tới.

## Đưa dải Flash Sale lên trang chủ

Cách dễ nhất là dùng màn **Layout block** trong quản trị, không cần sửa file nào:

1. Vào **Layout block** → **Add block layout**.

2. Chọn **Type** = `View`.

3. Ở ô **Position**, chọn vị trí muốn hiển thị (ví dụ *Position Bottom*); ở ô **Page**, chọn `front_home — Home page`.

4. Ở ô **Text**, chọn khối tên **`product_flash_sale`**.

   Đây là khối của plugin, có sẵn ngay khi plugin được bật. (Khối `shop_product_promotion` ngay cạnh là khối "sản phẩm khuyến mãi" có sẵn của cửa hàng — khối khác, không có đồng hồ đếm ngược.)

5. Tick **Active** rồi **Submit**, sau đó tải lại trang chủ. Nếu đang có đợt chạy, bạn sẽ thấy dải sản phẩm cuộn ngang, mỗi thẻ có phần trăm giảm giá, số đã bán / còn lại và đồng hồ đếm ngược. Nếu không có đợt nào, dải này tự ẩn — trang chủ không bị khoảng trống.

> **Không thấy `product_flash_sale` trong ô Text?** Kiểm tra plugin đang **Enable** (khối chỉ xuất hiện khi plugin bật), rồi chạy `php artisan optimize:clear` và tải lại màn Layout block.

**Site dùng template riêng** (tên khác `GP247Front`): **không cần làm gì thêm** — từ bản 2.0.1 khối được plugin đăng ký thẳng với hệ thống, không phụ thuộc tên template, nên nó có mặt trong ô Text với mọi template.

**Muốn sửa giao diện của dải?** Đừng sửa file trong thư mục plugin — bản cập nhật plugin sẽ ghi đè. Có ba cách giữ được bản sửa, xem mục [Tuỳ biến giao diện dải Flash Sale](#tuỳ-biến-giao-diện-dải-flash-sale) bên dưới.

**Cách chèn tay** (khi bạn muốn ghim cứng vị trí trong giao diện, không qua Layout block): mở file trang chủ của template, ví dụ

```
app/GP247/Templates/{TÊN_TEMPLATE}/screen/home.blade.php
```

rồi chèn đúng một dòng sau vào chỗ muốn hiển thị:

```blade
@include('Plugins/ProductFlashSale::blocks.product_flash_sale')
```

**Đặt một liên kết tới trang Flash Sale** ở menu hay bất cứ đâu trong giao diện:

```blade
<a href="{{ gp247_route_front('product_flash_sale.index') }}">Flash Sale</a>
```

Dùng `gp247_route_front(...)` chứ không phải `route(...)`, để đường dẫn giữ đúng mã ngôn ngữ khi site bật đa ngôn ngữ.

**Muốn đổi giao diện trang danh sách** mà không sửa mã plugin: tạo file

```
app/GP247/Templates/{TÊN_TEMPLATE}/Plugins/ProductFlashSale/front_flash_sale.blade.php
```

Bản của template sẽ được dùng thay cho bản mặc định của plugin.

## Tuỳ biến giao diện dải Flash Sale

Bạn sửa được giao diện của dải mà **không đụng vào mã plugin**, và bản sửa vẫn còn nguyên sau khi cập nhật plugin. Có ba cách, chọn cách nhẹ nhất đủ dùng.

**Cách 1 — Bọc dải (khuyên dùng khi chỉ muốn thêm thắt).** Giữ nguyên dải của plugin, thêm markup của bạn ở quanh nó. Tạo file:

```
app/GP247/Templates/{TÊN_TEMPLATE}/blocks/product_flash_sale.blade.php
```

với nội dung:

```blade
<div class="container-x pt-6">
    <h2 class="section-title">Giờ vàng hôm nay</h2>
</div>

@includeIf('Plugins/ProductFlashSale::blocks.product_flash_sale')
```

Dùng `@includeIf` chứ không phải `@include`: nếu sau này bạn gỡ plugin, `@includeIf` chỉ đơn giản không hiện gì, còn `@include` sẽ làm trang báo lỗi "View not found".

**Cách 2 — Viết lại hẳn dải.** Cũng là file ở đường dẫn trên, nhưng bạn tự viết toàn bộ markup và tự lấy dữ liệu:

```blade
@php
    $products = gp247_product_flash(10);
@endphp

@foreach ($products as $product)
    {{-- markup của riêng bạn --}}
@endforeach
```

**Cách 3 — Ghi đè một view bên trong plugin.** Khi chỉ muốn đổi một mảnh nhỏ (ví dụ thẻ sản phẩm, hoặc trang danh sách) mà giữ nguyên phần còn lại, tạo file cùng tên dưới:

```
resources/views/vendor/Plugins/ProductFlashSale/
```

Ví dụ `resources/views/vendor/Plugins/ProductFlashSale/partials/flash_card.blade.php` sẽ thay thế thẻ sản phẩm của plugin ở mọi nơi nó xuất hiện. Cách này áp dụng cho mọi view của plugin.

> Lưu ý chung: cách 2 và cách 3 tạo ra **một bản sao đóng băng** — plugin cập nhật giao diện về sau thì phần bạn đã ghi đè không nhận thay đổi đó. Cách 1 không có nhược điểm này. Muốn quay về bản gốc, chỉ cần xoá file bạn đã tạo.

## Cách hệ thống đếm suất

Suất của đợt bán được trừ và hoàn tự động theo vòng đời đơn hàng — bạn không phải chỉnh tay:

- **Khi khách đặt hàng**: hệ thống trừ suất ngay trong lúc tạo đơn. Hai khách bấm mua cùng lúc không thể mua vượt số suất: người sau bị từ chối và đơn của họ không được tạo.
- **Khi huỷ đơn, xoá đơn, hoặc xoá một dòng hàng trong đơn**: suất được trả lại cho đợt bán, đúng như hàng được trả về kho.
- **Khi mở lại một đơn đã huỷ**: hệ thống trừ suất lần nữa. Nếu đợt đã hết suất, việc mở lại bị từ chối — giống hệt cách hệ thống từ chối khi kho không còn hàng.
- Sản phẩm không nằm trong đợt Flash Sale nào thì không bao giờ bị plugin này chặn.

## Chạy trên site nhiều cửa hàng (MultiStore / MultiVendor)

Một đợt Flash Sale thuộc về cửa hàng sở hữu sản phẩm, nên phạm vi hiển thị đi theo đúng luật chung của site:

- **Sàn nhiều gian hàng (MultiVendor)**: storefront chung hiển thị đợt của mọi gian hàng đang hoạt động. Nếu một gian hàng bị tắt, đợt của gian hàng đó biến mất khỏi sàn.
- **Nhiều cửa hàng theo tên miền (MultiStore)**: mỗi cửa hàng chỉ thấy đợt của chính mình.
- **Trong quản trị**: người quản trị của một cửa hàng chỉ nhìn thấy và chỉ chọn được sản phẩm của cửa hàng mình. Quản trị viên gốc nhìn thấy đợt của mọi cửa hàng, và danh sách có thêm dòng nhỏ ghi tên cửa hàng sở hữu.
- **Khi dùng cùng giá theo nhóm khách hàng** (MultiVendor Pro): giá Flash Sale là giá nền, mức giá theo nhóm chỉ có thể thấp hơn chứ không cao hơn. Khách mua với mức giá thấp hơn đó vẫn tiêu một suất của đợt.

Lưu ý: màn tạo đợt Flash Sale nằm ở khu quản trị gốc. Trong mô hình sàn, người của gian hàng không tự tạo đợt Flash Sale ở khu vendor được — đợt bán do quản trị sàn tạo.

## Điều kiện & ràng buộc (hiểu trước khi thao tác)

**Khi tạo hoặc sửa một đợt bán**

- **Phải chọn một sản phẩm có trong danh sách gợi ý** — danh sách chỉ gồm sản phẩm đơn/sản phẩm dựng (không phải sản phẩm nhóm) và thuộc cửa hàng của bạn. Hệ thống kiểm tra lại lần nữa khi lưu, nên không thể "lách" bằng cách sửa dữ liệu gửi lên.
- **Mỗi sản phẩm chỉ có một đợt Flash Sale** — hai đợt trên cùng một sản phẩm sẽ tranh nhau cùng một mức giá khuyến mãi, nên hệ thống chặn ngay từ đầu.
- **Số lượng bán phải từ 1 trở lên** — một đợt không suất thì không có gì để bán.
- **Số lượng không được nhỏ hơn số đã bán** — nếu đã bán 4 mà sửa xuống 3, thanh tiến độ sẽ vượt 100% và không ai biết là do bán được hay do sửa số.
- **Thời điểm kết thúc phải sau thời điểm bắt đầu** — ngược lại thì đợt không bao giờ chạy.
- **Giá khuyến mãi là số, không âm**, nhập theo tiền cơ sở của site.

**Khi sản phẩm hiển thị trên trang bán hàng**

- Đợt chỉ hiện khi **đang trong khung giờ** (so tới từng phút, không phải cả ngày) và **công tắc Trạng thái đang bật**.
- Đợt **hết suất** (`đã bán = số lượng`) tự rời khỏi dải Flash Sale.
- Sản phẩm vẫn phải đạt điều kiện hiển thị chung của cửa hàng: **đang bán, đã duyệt, thuộc cửa hàng đang xem, và có mô tả ở ngôn ngữ hiện tại**. Thiếu một trong số đó thì dù đã tạo đợt, sản phẩm vẫn không hiện.

**Khi khách mua hàng**

- Mua quá số suất còn lại thì **đơn bị từ chối**, không phải "mua trước tính sau" — đây là lý do lời hứa "chỉ N suất" giữ được.
- Mở lại một đơn đã huỷ cũng bị từ chối nếu đợt đã hết suất.

## Bật, tắt và gỡ plugin

- **Tắt (Disable)**: cả màn quản trị lẫn trang Flash Sale trên site ngừng hoạt động. Dữ liệu vẫn còn nguyên, bật lại là chạy tiếp.
- **Gỡ (Uninstall)**: xoá cấu hình, mục menu và bảng dữ liệu `shop_product_flash` của plugin. Các mức giá khuyến mãi đã tạo cho sản phẩm không bị xoá theo. Khối `product_flash_sale` biến mất khỏi danh sách chọn cùng với plugin (plugin không để lại file nào trong template), và vị trí đã gắn khối đó chỉ đơn giản không hiển thị gì.
- Việc bật/tắt plugin áp dụng cho **toàn site**, không tách riêng theo từng cửa hàng.

## Thông tin kỹ thuật

- Đường dẫn trang bán hàng: `GET /plugin/product_flash_sale/index` (tên route `product_flash_sale.index`).
- Đường dẫn quản trị: `{GP247_ADMIN_PREFIX}/product_flash_sale`, `/create`, `/edit/{id}`.
- Bảng dữ liệu: `shop_product_flash` gồm `id`, `product_id`, `stock` (số suất), `sold` (đã bán), `sort`.
- Giá và khung giờ lưu ở bảng khuyến mãi có sẵn của cửa hàng: `shop_product_promotion`.
- Namespace view/ngôn ngữ: `Plugins/ProductFlashSale`.
- Khối trang chủ được đăng ký vào `config('gp247-config.front.layout_block_views')` (khoá `product_flash_sale`) từ `Provider.php`, không ship thư mục template và không copy file vào `app/GP247/Templates`. Hệ thống tra file của template **trước**, nên bản bạn tự viết luôn thắng.
- Các hàm dùng lại được trong giao diện:

  ```php
  gp247_product_flash($limit = 8, $paginate = false);          // danh sách sản phẩm đang Flash Sale
  gp247_product_flash_check_over($productId, $quantity);       // còn đủ suất không (true = còn)
  gp247_product_flash_update_stock($productId, $quantity);     // trừ suất, trả false khi không đủ
  gp247_product_flash_release_stock($productId, $quantity);    // trả suất về đợt bán
  ```

  Ví dụ tự dựng danh sách trong giao diện:

  ```blade
  @php
      $products = gp247_product_flash(8);
  @endphp

  @foreach ($products as $product)
      <div>{{ $product->getName() }} — đã bán {{ $product->pf_sold }}/{{ $product->pf_stock }}</div>
  @endforeach
  ```

## Hỏi & Đáp (Q&A)

**Câu 1: Tôi tạo đợt xong nhưng trang Flash Sale trống, vì sao?**

→ Thường là do một trong ba lý do: chưa tới giờ bắt đầu hoặc đã quá giờ kết thúc; công tắc Trạng thái đang tắt; hoặc sản phẩm chưa đạt điều kiện hiển thị chung (chưa bật bán, chưa được duyệt, hoặc thiếu mô tả ở ngôn ngữ đang xem).

**Câu 2: Đồng hồ đếm ngược lấy mốc thời gian ở đâu?**

→ Lấy từ thời điểm **Kết thúc** của chính đợt bán đó. Mỗi thẻ sản phẩm có đồng hồ riêng theo đợt của nó.

**Câu 3: Khách huỷ đơn thì số suất có được trả lại không?**

→ Có, tự động. Huỷ đơn, xoá đơn hoặc xoá một dòng hàng trong đơn đều trả suất về cho đợt bán.

**Câu 4: Hai khách cùng bấm mua suất cuối cùng thì sao?**

→ Chỉ một người mua được. Người còn lại nhận thông báo vượt số lượng và đơn không được tạo — hệ thống trừ suất theo cách chống tranh chấp, không phải "ai cũng qua rồi tính sau".

**Câu 5: Tôi muốn dừng đợt ngay lập tức?**

→ Tắt công tắc **Trạng thái** của dòng đó, hoặc sửa thời điểm Kết thúc về quá khứ. Xoá dòng cũng được, nhưng khi đó mức giá khuyến mãi của sản phẩm cũng bị gỡ.

**Câu 6: Cập nhật plugin lên bản mới có mất các đợt đã tạo không?**

→ Không. Cài lại và cập nhật đều giữ nguyên dữ liệu; chỉ thao tác **Uninstall** mới xoá bảng.

**Câu 7: Sản phẩm vừa có Flash Sale vừa có giá theo nhóm khách hàng thì khách trả giá nào?**

→ Trả mức thấp hơn. Giá Flash Sale là giá nền, mức giá theo nhóm chỉ có thể kéo xuống thấp hơn. Dù trả giá nào, khách vẫn tiêu một suất của đợt.

**Câu 8: Trên sàn nhiều gian hàng, gian hàng có tự tạo đợt Flash Sale được không?**

→ Không. Màn tạo đợt nằm ở khu quản trị gốc, nên đợt bán do quản trị sàn tạo.

**Câu 9: Tôi tắt plugin cho riêng một cửa hàng được không?**

→ Không. Bật/tắt plugin áp dụng cho toàn site. Tuy nhiên cửa hàng nào không tạo đợt nào thì cũng không có gì hiển thị, nên thực tế không ảnh hưởng tới các cửa hàng khác.

**Câu 10: Dải Flash Sale không hiện trên trang chủ dù đã có đợt đang chạy?**

→ Plugin không tự đặt dải lên trang chủ — bạn chọn vị trí. Kiểm tra đã thêm khối `product_flash_sale` ở màn **Layout block** cho trang `front_home` chưa, hoặc template đã có dòng `@include('Plugins/ProductFlashSale::blocks.product_flash_sale')` chưa. Lưu ý đừng nhầm với khối `shop_product_promotion` — đó là khối khuyến mãi có sẵn của cửa hàng.

---

<sub>📅 **Cập nhật lần cuối:** 2026-09-22 · ✍️ **Tác giả (Author):** GP247</sub>
