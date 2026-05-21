<?php
/**
 * @var float $totalTimeMs
 * @var bool $isCacheHit
 * @var array $slowQueries
 * @var bool $hasSlowQueries
 */
?>
<div id="global-profiler" style="background: #141416; color: #FFF; font-family: monospace; position: fixed; bottom: 0; left: 0; right: 0; z-index: 999999; box-shadow: 0px -5px 25px rgba(0,0,0,0.7); font-size: 13px; transition: transform 0.2s ease-in-out;">

    <!-- Кнопка Свернуть/Развернуть -->
    <button onclick="toggleProfiler()" style="position: absolute; top: -28px; right: 20px; background: #141416; color: #00F0FF; border: 1px solid #2C2C2E; border-bottom: none; border-radius: 4px 4px 0 0; padding: 5px 15px; cursor: pointer; font-family: monospace; font-size: 11px; font-weight: bold; box-shadow: 0px -5px 10px rgba(0,0,0,0.3);" id="profiler-toggle-btn">⬇️ СВЕРНУТЬ</button>

    <!-- Внутренний контент панели -->
    <div id="profiler-body" style="padding: 20px; border-top: 4px solid #00F0FF;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: <?= $hasSlowQueries ? '15px' : '0' ?>;">
            <div>
                <span style="font-size: 16px; font-weight: bold; color: #00F0FF; margin-right: 20px;">🛠️ GLOBAL PAGE PROFILER</span>
                КЭШ: <span style="color: <?= $isCacheHit ? '#55FF55' : '#FF5555' ?>; font-weight: bold;"><?= $isCacheHit ? 'ИСПОЛЬЗОВАЛСЯ (HIT)' : 'НЕ ИСПОЛЬЗОВАЛСЯ (MISS)' ?></span>
            </div>
            <div style="font-size: 15px;">
                🚀 <b>ВРЕМЯ СТРАНИЦЫ:</b> <span style="color: <?= $totalTimeMs > 40 ? '#FF5555' : '#55FF55' ?>; font-weight: bold;"><?= number_format($totalTimeMs, 2) ?> ms</span>
            </div>
        </div>

        <?php if ($hasSlowQueries): ?>
            <div style="background: #1C1C1E; padding: 15px; border-radius: 4px; border-left: 4px solid #FF5555; max-height: 150px; overflow-y: auto;">
                <b style="color: #FF5555; font-size: 14px;">⚠️ ОБНАРУЖЕНЫ МЕДЛЕННЫЕ ЗАПРОСЫ (> 50 ms):</b>
                <ul style="list-style: none; padding-left: 0; margin-top: 10px; margin-bottom: 0;">
                    <?php foreach ($slowQueries as $query): ?>
                        <li style="margin-bottom: 12px; border-bottom: 1px solid #2C2C2E; padding-bottom: 8px;">
                            ⏱️ <span style="color: #FF5555; font-weight: bold;"><?= number_format($query['duration'], 2) ?> ms</span> |
                            <code style="color: #E5E5EA;"><?= htmlspecialchars($query['sql']) ?></code>
                            <?php if (!empty($query['params'])): ?>
                                <br><small style="color: #8E8E93;">Параметры: <?= json_encode($query['params'], JSON_UNESCAPED_UNICODE) ?></small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleProfiler() {
        const body = document.getElementById('profiler-body');
        const btn = document.getElementById('profiler-toggle-btn');

        if (body.style.display !== 'none') {
            body.style.display = 'none';
            btn.innerText = '⚠️ РАЗВЕРНУТЬ';
            localStorage.setItem('profiler_collapsed', '1');
        } else {
            body.style.display = 'block';
            btn.innerText = '⬇️ СВЕРНУТЬ';
            localStorage.removeItem('profiler_collapsed');
        }
    }

    if (localStorage.getItem('profiler_collapsed') === '1') {
        document.getElementById('profiler-body').style.display = 'none';
        document.getElementById('profiler-toggle-btn').innerText = '⚠️ РАЗВЕРНУТЬ';
    }
</script>
