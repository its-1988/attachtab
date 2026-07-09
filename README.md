# attachtab — Вкладка вложений

Плагин для **GLPI 11.0.7+** (PHP 8.2+). Добавляет на форму **заявки, изменения и
проблемы** вкладку **«Вложения»** — единый список всех файлов объекта плюс загрузка
новых. Без своих таблиц БД, без правок ядра.

<img width="1035" height="497" alt="imgru" src="https://github.com/user-attachments/assets/7f068885-0fed-4604-965a-e333a1390c20" />


## Что показывает вкладка

Все документы объекта, собранные **тем же запросом, каким ядро строит таймлайн**
(`CommonITILObject::getAssociatedDocumentsCriteria()`), поэтому вкладка всегда
согласована с таймлайном:

- файлы, прикреплённые **напрямую** к заявке (в т.ч. при создании);
- вложения **комментариев** (ITILFollowup);
- вложения **задач** (TicketTask / ChangeTask / ProblemTask);
- вложения **решений** (ITILSolution);
- вложения **согласований** (TicketValidation / ChangeValidation).

Колонки: файл (ссылка на скачивание), источник (Заявка / Комментарий / Задача /
Решение / Согласование — родные названия и переводы GLPI), кто добавил, дата.
Счётчик на ярлыке вкладки — число «настоящих» вложений (без картинок из текста),
если в настройках включён показ счётчиков на вкладках.

### Картинки, вставленные в текст

Скриншоты, вставленные в текст описания/комментариев из буфера, GLPI тоже хранит как
документы (с пометкой `timeline_position = -1`). Чтобы они не засоряли список, по
умолчанию они **скрыты**; переключатель «Показать изображения из текста (N)» выводит
их отдельными строками со значком.

## Загрузка файлов

Сверху вкладки — **нативная форма GLPI** «Добавить документ» (та же, что на вкладке
Документы у других объектов): права, CSRF, дедупликация по sha1 — всё делает ядро.
Загруженный файл становится обычным документом заявки и **появляется в таймлайне**.
Форма видна тем, у кого есть нативные права: чтение объекта + право прикреплять
документы + право видеть документы.

## Открепление

Кнопка «открепить» есть только у документов, **прикреплённых напрямую** к объекту.
Право — нативное право на удаление связи «документ‑объект», ровно то же, каким ядро
защищает массовое действие «удалить» в списках документов (`Document_Item` PURGE:
право изменения документа или права изменения самой заявки). Кому нельзя — кнопка не
показывается, а сервер дополнительно проверяет право при запросе. Удаляется только
**связь** — сам документ остаётся в системе. Вложения комментариев/задач/решений из
вкладки не открепляются: они принадлежат своим записям таймлайна.

## Права на просмотр и скачивание

Список видят все, кто видит объект. Скачивание идёт нативным
`front/document.send.php` с контекстом объекта — ядро разрешает его любому, кто
может **читать заявку** (даже без права на «Документы»), включая вложения
комментариев. Вложения приватных комментариев не показываются тем, кому не виден
сам комментарий (это гарантирует критерий ядра).

## Ограничения

- **Упрощённый интерфейс (helpdesk)** GLPI 11 не отображает вкладок вовсе — это
  поведение ядра; вкладка доступна в стандартном интерфейсе.
- Массовые действия и скачивание всех файлов архивом не реализованы.

## Установка

1. Распаковать папку `attachtab` в `…/glpi/plugins/` (имя папки — именно `attachtab`).
2. **Настройки → Плагины** → у «Вкладка вложений» нажать **Установить**, затем
   **Активировать**.

Обновление: заменить файлы папки и обновить страницу (таблиц БД нет, переустановка
не нужна).

## Проверка после установки (чеклист)

1. Открыть заявку, где файлы были добавлены и напрямую, и в комментариях — на
   вкладке «Вложения» видны все, с верной подписью источника.
2. Счётчик на ярлыке = числу вложений без картинок из текста.
3. Переключатель показывает вставленные в текст картинки отдельными строками.
4. Загрузить файл через вкладку — он появился и в списке, и в таймлайне заявки.
5. У файла, прикреплённого напрямую, есть кнопка открепления; у файла из
   комментария — нет. Открепление убирает строку, документ остаётся в «Управление →
   Документы».
6. Пользователь без права на «Документы», но с доступом к заявке: список виден,
   файлы скачиваются, формы загрузки нет; кнопка открепления есть только у тех,
   кто может менять документ или заявку (как в нативных списках документов).
7. Вложение приватного комментария не видно пользователю, которому не виден сам
   комментарий.
8. То же самое работает на Изменении и Проблеме.

## Совместимость

- GLPI **11.0.7** и новее (код сверен с исходниками 11.0.8; используемые API в
  11.0.7 и 11.0.8 идентичны).
- PHP 8.2+.

## Лицензия

GPLv3+.

---

# English (summary)

**attachtab** — an "Attachments" tab for GLPI **11.0.7+** (PHP 8.2+) on **Ticket,
Change and Problem** forms. No DB tables, no core edits.

- **One list with every file of the object**: attached directly *and* carried by
  followups, tasks, solutions and validations. Built with the same criteria the
  core timeline uses (`getAssociatedDocumentsCriteria()`), so it always matches
  the timeline and respects private-followup visibility.
- Columns: file (download link), source (Ticket / Followup / Task / Solution /
  Validation — native type names), added by, date. Tab badge counts real
  attachments when "Show count on tabs" is enabled.
- **Images pasted into text** (`timeline_position = -1`) are hidden by default;
  a toggle shows them.
- **Upload**: the native GLPI "add a document" form — rights, CSRF and sha1
  dedup are core behaviour; the file becomes a regular document of the object
  and shows up in the timeline.
- **Detach**: only for directly-linked documents, guarded by the native
  per-link right (`Document_Item` PURGE — update right on the document or on
  the parent object, same as the core massive-action purge). Only the link is
  removed, the document itself is kept. Followup/task/solution attachments are
  read-only here.
- **Download rights**: anyone who can read the object can download its files
  (native `document.send.php` context), even without the Documents right.
- **Limitation**: the GLPI 11 simplified (helpdesk) interface renders no tabs
  at all — the tab is available in the standard interface.
- **Install**: unzip into `plugins/` (folder name must be `attachtab`), then
  Setup → Plugins → Install → Enable. Updates: just replace files (no DB).
- Locales: en_GB, ru_RU. License: GPLv3+.
