$(function () {
    // Load cart data on page load
    loadCartData();

    // Update quantity
    $(document).on("change", ".quantity-input", function () {
        const productId = $(this).data("product-id");
        const quantity = parseInt($(this).val());
        updateCartItem(productId, quantity);
    });

    // Increase quantity
    $(document).on("click", ".btn-increase", function () {
        const input = $(this).siblings(".quantity-input");
        const currentVal = parseInt(input.val());
        input.val(currentVal + 1).trigger("change");
    });

    // Decrease quantity
    $(document).on("click", ".btn-decrease", function () {
        const input = $(this).siblings(".quantity-input");
        const currentVal = parseInt(input.val());
        if (currentVal > 0) {
            input.val(currentVal - 1).trigger("change");
        }
    });

    // Remove item
    $(document).on("click", ".remove-item", function () {
        const productId = $(this).closest(".cart-item").data("product-id");
        removeCartItem(productId);
    });

    // Clear cart
    $(document).on("click", "#clear-cart", function () {
        if (confirm("Yakin ingin mengosongkan keranjang?")) {
            clearCart();
        }
    });

    function loadCartData() {
        $.ajax({
            url: cartDataUrl,
            method: "GET",
            success: function (response) {
                if (response.success) {
                    renderCart(response);
                }
            },
            error: function () {
                toastr.error("Gagal memuat data keranjang");
            },
        });
    }

    function renderCart(data) {
        if (data.products.length === 0) {
            $("#cart-empty").show();
            $("#cart-content").hide();
            $("#cart-actions").hide();
            return;
        }

        $("#cart-empty").hide();
        $("#cart-content").show();
        $("#cart-actions").show();

        // Render cart items
        let cartItemsHtml = "";
        data.products.forEach(function (product) {
            const quantity = data.cart[product.id] || 0;
            const img = product.image;
            const useStorage =
                img && img !== "uploads/images/products/product-1.png";
            const finalPrice =
                product.discount > 0
                    ? product.selling_price * product.discount
                    : product.selling_price;

            let imageHtml = "";
            if (img) {
                const imageUrl = useStorage
                    ? storageUrl + "/" + img
                    : baseUrl + img;
                imageHtml =
                    '<img src="' +
                    imageUrl +
                    '" class="w-100 h-100" style="object-fit: cover;" alt="' +
                    product.product_name +
                    '">';
            } else {
                imageHtml =
                    '<div class="d-flex justify-content-center align-items-center text-muted">No Image</div>';
            }

            let discountBadge = "";
            if (product.discount > 0) {
                discountBadge =
                    '<div class="mt-1"><span class="badge bg-danger">-' +
                    (product.discount * 100).toFixed(0) +
                    "%</span></div>";
            }

            let originalPrice = "";
            if (product.discount > 0) {
                originalPrice =
                    '<small class="text-decoration-line-through text-muted">Rp ' +
                    (product.selling_price * quantity).toLocaleString("id-ID") +
                    "</small>";
            }

            cartItemsHtml +=
                '<div class="row align-items-center py-3 border-bottom cart-item" data-product-id="' +
                product.id +
                '">' +
                '<div class="col-3 col-md-2">' +
                '<div class="ratio ratio-1x1 bg-light rounded">' +
                imageHtml +
                "</div>" +
                "</div>" +
                '<div class="col-6 col-md-4">' +
                '<h6 class="mb-1">' +
                product.product_name +
                "</h6>" +
                '<small class="text-muted">' +
                product.brand_name +
                "</small>" +
                discountBadge +
                "</div>" +
                '<div class="col-3 col-md-2">' +
                '<input type="number" class="form-control text-center quantity-input" style="width: 70px;" value="' +
                quantity +
                '" min="0" data-product-id="' +
                product.id +
                '">' +
                "</div>" +
                '<div class="col-6 col-md-2 text-end">' +
                '<div class="fw-semibold">Rp ' +
                (finalPrice * quantity).toLocaleString("id-ID") +
                "</div>" +
                originalPrice +
                "</div>" +
                '<div class="col-6 col-md-2 text-end">' +
                '<button class="btn btn-outline-danger btn-sm remove-item">' +
                '<i class="ti ti-trash"></i>' +
                "</button>" +
                "</div>" +
                "</div>";
        });

        $("#cart-items").html(cartItemsHtml);

        // Render cart summary
        let summaryHtml =
            '<div class="d-flex justify-content-between mb-2">' +
            "<span>Subtotal (" +
            data.totalItems +
            " item)</span>" +
            "<span>Rp " +
            data.subtotal.toLocaleString("id-ID") +
            "</span>" +
            "</div>";

        if (data.totalDiscount > 0) {
            summaryHtml +=
                '<div class="d-flex justify-content-between mb-2 text-success">' +
                "<span>Diskon</span>" +
                "<span>-Rp " +
                data.totalDiscount.toLocaleString("id-ID") +
                "</span>" +
                "</div>";
        }

        summaryHtml +=
            "<hr>" +
            '<div class="d-flex justify-content-between fw-semibold">' +
            "<span>Total</span>" +
            "<span>Rp " +
            data.subtotal.toLocaleString("id-ID") +
            "</span>" +
            "</div>" +
            '<div class="d-grid mt-3">' +
            '<a href="' +
            checkoutUrl +
            '" class="btn btn-primary">Lanjut ke Pembayaran</a>' +
            "</div>";

        $("#cart-summary").html(summaryHtml);
    }

    function updateCartItem(productId, quantity) {
        $.ajax({
            url: updateCartUrl,
            method: "PUT",
            data: {
                product_id: productId,
                quantity: quantity,
                _token: csrfToken,
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $(".cart-count").text(response.cart_count);
                    loadCartData(); // Reload cart data
                }
            },
            error: function (response) {
                console.log(response);
                toastr.error("Gagal memperbarui keranjang");
            },
        });
    }

    function removeCartItem(productId) {
        $.ajax({
            url: removeCartUrl,
            method: "DELETE",
            data: {
                product_id: productId,
                _token: csrfToken,
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $(".cart-count").text(response.cart_count);
                    loadCartData(); // Reload cart data
                }
            },
            error: function () {
                toastr.error("Gagal menghapus item");
            },
        });
    }

    function clearCart() {
        $.ajax({
            url: clearCartUrl,
            method: "DELETE",
            data: {
                _token: csrfToken,
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $(".cart-count").text("0");
                    loadCartData(); // Reload cart data
                }
            },
            error: function () {
                toastr.error("Gagal mengosongkan keranjang");
            },
        });
    }
});
