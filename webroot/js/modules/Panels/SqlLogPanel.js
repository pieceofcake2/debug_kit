/**
 * SQL Log panel: runs EXPLAIN for a query.
 *
 * Ported from the DebugKit 2.x toolbar (DEBUGKIT.sqlLog) by Piece of Cake 2.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
export default (($) => {
  const onEvent = () => {
    $(document).on('click', '#sql_log-tab .sql-explain-link', function explain(event) {
      event.preventDefault();
      const form = $(this.form);
      const dbName = form.find('input[name*=ds]').val() || 'default';
      const target = $(`[id="sql-log-explain-${dbName}"]`);

      $.ajax({
        url: this.form.action,
        data: form.serialize(),
        type: 'POST',
        success(response) {
          target.html(response);
        },
        error() {
          $('<p class="c-flash c-flash--warning">').text('Could not fetch EXPLAIN for query.').appendTo(target.empty());
        },
      });
    });
  };

  return {
    onEvent,
  };
})(jQuery);
