</main>

<div id="toast-container" class="toast-container" aria-live="polite"></div>

<footer class="app-footer">
    <div class="footer-container">
        <p>&copy; <?= date('Y') ?> Chess PWA &bull; Gemaakt voor MBO Software Developer Level 3</p>
    </div>
</footer>

<script src="../javascript/script.js"></script>
<?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// PWA Service Worker registratie
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('../sw.js')
            .then(reg => console.log('ServiceWorker geregistreerd:', reg.scope))
            .catch(err => console.log('ServiceWorker registratie mislukt:', err));
    });
}
</script>
</body>
</html>
