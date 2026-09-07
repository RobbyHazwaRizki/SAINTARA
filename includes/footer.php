<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// includes/footer.php — Closing Shell + Scripts
// ============================================================
?>

  <!-- Ella Float Button (muncul di semua halaman kecuali login) -->
  <?php if (!($hideElla ?? false)): ?>
  <div class="ella-float" id="ellaFloat" style="display:none" data-hidden-reason="Fitur chat AI (Claude) belum aktif — hapus style ini untuk menampilkan lagi">
    <div class="ella-bubble" id="ellaBubble" style="display:none">
      Hai! Mau tanya apa? 🐘
    </div>
    <div class="ella-float-btn" id="ellaFloatBtn" onclick="toggleEllaPanel()" aria-label="Tanya Ella">
      <!-- SVG Ella di-inject oleh ella.js -->
    </div>
  </div>

  <!-- Ella Chat Panel -->
  <div class="ella-panel" id="ellaPanel" role="dialog" aria-label="Chat dengan Ella">
    <div class="ella-panel-header">
      <span style="font-size:1.3rem">🐘</span>
      <span class="ella-panel-title">Tanya Ella</span>
      <div class="ella-panel-close" onclick="toggleEllaPanel()" aria-label="Tutup">✕</div>
    </div>
    <div class="ella-chat-area" id="ellaChatArea">
      <div class="chat-msg ella">
        Halo! Aku Ella 🐘 Aku siap bantu kamu belajar IPA. Mau tanya apa?
      </div>
    </div>
    <div class="ella-input-row">
      <input type="text" class="ella-input" id="ellaInput"
             placeholder="Ketik pertanyaanmu..."
             onkeypress="if(event.key==='Enter') kirimPesan()"
             maxlength="200">
      <button class="ella-send-btn" onclick="kirimPesan()" aria-label="Kirim">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/>
        </svg>
      </button>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /.app -->

<!-- JS — load di akhir untuk performa optimal -->
<script src="/SAINTARA/assets/js/ella.js"></script>
<script src="/SAINTARA/assets/js/data.js"></script>
<script src="/SAINTARA/assets/js/app.js"></script>
<?php if (!empty($extraJS)): ?>
  <?php foreach ($extraJS as $js): ?>
  <script src="/SAINTARA/assets/js/<?= htmlspecialchars($js) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>