<div id="admin-toasts" class="admin-toasts" aria-live="polite" aria-relevant="additions"></div>
<style>
    .admin-toasts{position:fixed;top:20px;right:20px;z-index:2000;display:grid;gap:10px;width:min(360px,calc(100vw - 40px));pointer-events:none}
    .admin-toast{display:flex;align-items:center;gap:16px;padding:14px 16px;border:1px solid #b8dfc2;border-radius:10px;background:#edf8ef;color:#24683b;box-shadow:0 6px 24px #20202a26;pointer-events:auto;animation:toast-appear .2s ease-out}
    .admin-toast.error{background:#fff0f0;color:#a52626;border-color:#edb9b9}
    .admin-toast button{margin-left:auto;padding:0 4px;border:0;background:transparent;color:inherit;font-size:22px;cursor:pointer}
    @keyframes toast-appear{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
    @media(prefers-reduced-motion:reduce){.admin-toast{animation:none}}
</style>
<script src="{{ asset('js/admin-toasts.js') }}"></script>
