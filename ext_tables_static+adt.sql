#
# Table structure for table 'tx_wecgroup_type'
#
DROP TABLE IF EXISTS tx_wecgroup_type;
CREATE TABLE tx_wecgroup_type (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	name varchar(48) DEFAULT '' NOT NULL,
	description text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

INSERT INTO tx_wecgroup_type (uid, pid, name, description, sorting) VALUES ('1', 0, 'Ministry', 'Ministry',1);
INSERT INTO tx_wecgroup_type (uid, pid, name, description, sorting) VALUES ('2', 0, 'Small Group', 'Small Group',2);
INSERT INTO tx_wecgroup_type (uid, pid, name, description, sorting) VALUES ('3', 0, 'Class', 'Class',3);