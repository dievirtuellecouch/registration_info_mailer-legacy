--
-- Table `tl_module`
--
CREATE TABLE `tl_module` (
  `rim_active` char(1) NOT NULL DEFAULT '',
  `rim_mailtemplate` int(5) unsigned NOT NULL DEFAULT '0',
  `rim_do_syslog` char(1) NOT NULL DEFAULT '',
  `rim_change_active` char(1) NOT NULL DEFAULT '',
  `rim_change_mailtemplate` int(5) unsigned NOT NULL DEFAULT '0',
  `rim_change_do_syslog` char(1) NOT NULL DEFAULT '',
  `rim_act_active` char(1) NOT NULL DEFAULT '',
  `rim_act_mailtemplate` int(5) unsigned NOT NULL DEFAULT '0',
  `rim_act_do_syslog` char(1) NOT NULL DEFAULT ''
);

--
-- Table `tl_member`
--

CREATE TABLE `tl_member` (
  `rim_send_mail` char(1) NOT NULL DEFAULT '1',
  `rim_deactivate_mailtemplate` int(5) unsigned NOT NULL DEFAULT '0',
  `rim_activate_mailtemplate` int(5) unsigned NOT NULL DEFAULT '0'
);
