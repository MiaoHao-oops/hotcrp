<?php
class SimulationExtension {
    static function login(Contact $user, Qrequest $qreq) {
        // Only registered by the loopback-only simulation configuration.
        if (!$user->has_account_here()) {
            $local = $user->conf->user_by_email($_SERVER["REMOTE_USER"] ?? "");
            if (!$local) {
                throw new RuntimeException("Local simulation account is missing");
            }
            $info = LoginHelper::login_complete(LoginHelper::check_external_login($local), $qreq);
            if (!($info["ok"] ?? false)) {
                throw new RuntimeException("Local account is disabled");
            }
            $qreq->redirect($info["redirect"]);
        } else if ($qreq->page() === "signin") {
            $qreq->redirect_hoturl("index");
        }
    }

    static function banner(Contact $user, Qrequest $qreq) {
        $id = defined('SIM_CONF_ID') ? SIM_CONF_ID : getenv('SIM_CONF_ID');
        $url = '/?conference=' . rawurlencode($id);
        echo '<div class="msg msg-info" style="margin:16px 0"><strong>本地模拟审稿 · Codex agents</strong> — ';
        echo '论文提交后自动开始模拟审稿。意见只适用于其标记的论文版本，不代表真实会议决定。 ';
        echo '<a href="' . htmlspecialchars($url) . '">会议规则、任务进度与重试 →</a></div>';
    }
}
