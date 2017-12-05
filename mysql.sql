SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

INSERT INTO `tables_priv` (`Host`, `Db`, `User`, `Table_name`, `Grantor`, `Timestamp`, `Table_priv`, `Column_priv`) VALUES
('%', 'kita', 'mandala', 'kids', 'root@localhost', '2017-10-22 20:00:00', 'Select,Insert,Update', ''),
('%', 'kita', 'mandala', 'groups', 'root@localhost', '2017-10-22 20:00:00', 'Select,Insert,Update', ''),
('%', 'kita', 'mandala', 'auth', 'root@localhost', '2017-10-22 20:00:00', 'Select', ''),
('%', 'kita', 'mandala', 'group_assignments', 'root@localhost', '2017-10-22 20:00:00', 'Select,Insert,Update', '');

INSERT INTO `user` (`Host`, `User`, `Password`, `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Reload_priv`, `Shutdown_priv`, `Process_priv`, `File_priv`, `Grant_priv`, `References_priv`, `Index_priv`, `Alter_priv`, `Show_db_priv`, `Super_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Execute_priv`, `Repl_slave_priv`, `Repl_client_priv`, `Create_view_priv`, `Show_view_priv`, `Create_routine_priv`, `Alter_routine_priv`, `Create_user_priv`, `Event_priv`, `Trigger_priv`, `Create_tablespace_priv`, `ssl_type`, `ssl_cipher`, `x509_issuer`, `x509_subject`, `max_questions`, `max_updates`, `max_connections`, `max_user_connections`, `plugin`, `authentication_string`, `password_expired`, `is_role`, `default_role`, `max_statement_time`) VALUES
('%', 'mandala', '*BD20A112364435C6479838B2E96A24E214F85FDD', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', 0, 0, 0, 0, '', '', 'N', 'N', '', '0.000000');
COMMIT;
