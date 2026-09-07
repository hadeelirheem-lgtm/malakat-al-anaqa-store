<?php
// صف طلب مشترك يُستخدم في تبويبي "الطلبات الواردة" و"تم توصيلها"
if (!isset($order)) return;
?>
<tr>
    <td><strong>#<?php echo (int)$order['id']; ?></strong></td>
    <td class="fw-bold"><?php echo htmlspecialchars($order['user_name'] ?? $order['name'] ?? 'زائر'); ?></td>
    <td><a href="tel:<?php echo htmlspecialchars($order['phone']); ?>" class="text-decoration-none fw-bold text-primary"><?php echo htmlspecialchars($order['phone']); ?></a></td>
    <td><small><?php echo htmlspecialchars($order['address']); ?></small></td>
    <td><span class="badge bg-light text-dark border p-2"><?php echo htmlspecialchars($order['order_items']); ?></span></td>
    <td class="fw-bold text-danger">$<?php echo number_format($order['total_price'], 2); ?></td>
    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($order['payment_method']); ?></span></td>

    <td>
        <?php if (!empty($order['receipt_path'])): ?>
            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo (int)$order['id']; ?>">
                <i class="fa-solid fa-image me-1"></i> عرض الإشعار
            </button>

            <div class="modal fade" id="receiptModal<?php echo (int)$order['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header border-0 bg-warning text-dark">
                            <h5 class="modal-title fw-bold">إشعار تحويل طلب #<?php echo (int)$order['id']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-3">
                            <img src="<?php echo htmlspecialchars($order['receipt_path']); ?>" class="img-fluid rounded-3 shadow-sm" style="max-height: 450px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <span class="text-muted small">دفع عند الاستلام</span>
        <?php endif; ?>
    </td>

    <td>
        <a href="invoice.php?id=<?php echo (int)$order['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold">
            <i class="fa-solid fa-file-invoice me-1"></i> الفاتورة
        </a>
    </td>

    <td>
        <span class="badge <?php echo $order['status'] === 'تم التوصيل' ? 'bg-success' : ($order['status'] === 'تمت القراءة' ? 'bg-primary' : 'bg-warning text-dark'); ?>">
            <?php echo htmlspecialchars($order['status']); ?>
        </span>
    </td>

    <td>
        <form method="POST" class="d-flex gap-1 justify-content-center">
            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
            <select name="status" class="form-select form-select-sm" style="width: 120px;">
                <option value="جديد" <?php if($order['status']=='جديد') echo 'selected'; ?>>جديد</option>
                <option value="تمت القراءة" <?php if($order['status']=='تمت القراءة') echo 'selected'; ?>>تمت القراءة</option>
                <option value="تم التوصيل" <?php if($order['status']=='تم التوصيل') echo 'selected'; ?>>تم التوصيل</option>
            </select>
            <button type="submit" name="update_status" class="btn btn-sm btn-dark"><i class="fa-solid fa-floppy-disk"></i></button>
        </form>
    </td>
</tr>
