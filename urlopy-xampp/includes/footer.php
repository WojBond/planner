<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
</div>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  });
}
</script>
</body>
</html>
