

# 録音機能について
##   📋 録音ストレージ設定 (.env)
  設定項目の詳細
  ### Recording Storage Settings
  MAX_STORAGE_SIZE=1024          #
  全体の最大ストレージ容量（MB）
  MAX_RECORDING_SIZE=100         #
  1録音あたりの最大ファイルサイズ（MB）
  MAX_RECORDING_TIME=3600        #
  1録音あたりの最大録音時間（秒）
  STORAGE_WARNING_THRESHOLD=80   # 警告表示の閾値（%）

  各設定の役割

  🗃️ MAX_STORAGE_SIZE=1024

  - 意味: 全録音ファイルの合計容量上限
  - 単位: MB（メガバイト）
  - 現在値: 1024MB = 1GB
  - 効果: この容量を超えると新規録音ができなくなる

  📁 MAX_RECORDING_SIZE=100

  - 意味: 1つの録音ファイルの最大サイズ
  - 単位: MB
  - 現在値: 100MB
  - 効果: 録音中にこのサイズに達すると自動停止

  ⏱️ MAX_RECORDING_TIME=3600

  - 意味: 1回の録音の最大時間
  - 単位: 秒
  - 現在値: 3600秒 = 60分 = 1時間
  - 効果: この時間に達すると自動的に録音停止

  ⚠️ STORAGE_WARNING_THRESHOLD=80

  - 意味: 容量警告を表示する閾値
  - 単位: パーセント
  - 現在値: 80%
  - 効果: 全体容量の80%を超えると画面に警告表示

  実際の動作例

  容量チェック

  - 現在使用量: 756MB / 1024MB (73.8%)
  - 警告レベル: 819MB (80%) を超えると⚠️警告表示

  録音制限

  - 📏 サイズ制限:
  100MBに達すると「ファイルサイズの上限に達しました」
  - ⏰ 時間制限: 1時間で「録音時間の上限に達しました」
  - 💾 容量不足:
  残り容量不足で「ストレージ容量が不足しています」

  設定変更方法

  設定を変更したい場合は.envファイルを編集：

  # より大きな容量に変更する例
  MAX_STORAGE_SIZE=2048          # 2GB に増加
  MAX_RECORDING_SIZE=200         # 200MB に増加
  MAX_RECORDING_TIME=7200        # 2時間 に増加
  STORAGE_WARNING_THRESHOLD=90   # 90% で警告に変更

  変更後は設定を反映：
  ./vendor/bin/sail artisan config:clear

  これらの設定により、録音機能が適切な容量管理を行い、サーバーの負荷を制御しています。


## 保存先
storage/app/private/recordings



# 参考資料
.htaccessによるアクセス制御をしたい
https://help.sakura.ad.jp/rs/2214/