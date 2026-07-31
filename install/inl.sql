-- 烽火棋 · 建库脚本
--
-- 目标环境 : MySQL 5.7+ / MariaDB 10.2+
-- 字符集   : utf8mb4 / utf8mb4_unicode_ci
--
-- 用法：
--   方式 A  浏览器打开 /install/ 走安装向导
--   方式 B  mysql -u <user> -p <database> < install/inl.sql
--
-- 注意：本文件不预置任何数据，包括管理员账号。导入后需自行插入一条
--       mok_admin 记录，详见下方 mok_admin 段的说明或 README「后台管理员」。

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for mok_admin
-- ----------------------------
DROP TABLE IF EXISTS `mok_admin`;
CREATE TABLE `mok_admin`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_admin）
-- ----------------------------
-- 走 install/ 向导安装时，由向导收集用户名与密码并写入 bcrypt 哈希。
-- 手工导入本文件的，请自行插入一条：
--   php -r "echo password_hash('你的密码', PASSWORD_BCRYPT, ['cost'=>12]), PHP_EOL;"
--   INSERT INTO `mok_admin` (`username`, `password`) VALUES ('你的账号', '上面输出的哈希');
-- ----------------------------
-- Table structure for mok_email_verify
-- ----------------------------
DROP TABLE IF EXISTS `mok_email_verify`;
CREATE TABLE `mok_email_verify`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱地址',
  `token` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '验证令牌',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0-未验证 1-已验证',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_token`(`token`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '邮箱验证表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_email_verify）
-- ----------------------------

-- ----------------------------
-- Table structure for mok_file_archive
-- ----------------------------
DROP TABLE IF EXISTS `mok_file_archive`;
CREATE TABLE `mok_file_archive`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `file_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件唯一标识（UUID）',
  `user_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '上传者用户ID，关联mok_user.id',
  `object_key` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'S3对象键名（完整路径）',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原始文件名',
  `file_size` bigint(20) NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
  `file_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '文件哈希值（SHA-256），用于去重',
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '文件MIME类型',
  `file_extension` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '文件扩展名',
  `bucket_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '存储桶名称',
  `etag` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'S3 ETag',
  `upload_method` tinyint(1) NOT NULL DEFAULT 1 COMMENT '上传方式：1-简单上传 2-分片上传',
  `upload_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '上传状态：0-上传中 1-已完成 2-失败 3-已删除',
  `download_count` int(11) NOT NULL DEFAULT 0 COMMENT '下载次数',
  `metadata` json NULL COMMENT '自定义元数据（JSON格式）',
  `upload_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
  `last_access_time` datetime NULL DEFAULT NULL COMMENT '最后访问时间',
  `delete_time` datetime NULL DEFAULT NULL COMMENT '删除时间（软删除）',
  `expire_time` datetime NULL DEFAULT NULL COMMENT '过期时间（自动清理）',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE COMMENT '索引：按用户查询上传文件',
  INDEX `idx_upload_time`(`upload_time`) USING BTREE COMMENT '索引：按上传时间排序',
  INDEX `idx_upload_status`(`upload_status`) USING BTREE COMMENT '索引：按上传状态筛选',
  INDEX `idx_object_key`(`object_key`(255)) USING BTREE COMMENT '索引：按对象键名查询',
  INDEX `idx_user_status`(`user_id`, `upload_status`) USING BTREE COMMENT '联合索引：查询用户指定状态的文件',
  INDEX `idx_expire_time`(`expire_time`, `upload_status`) USING BTREE COMMENT '索引：查询过期文件'
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文件归档索引表 - 记录所有S3文件元数据' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_file_archive）
-- ----------------------------

-- ----------------------------
-- Table structure for mok_match_record
-- ----------------------------
DROP TABLE IF EXISTS `mok_match_record`;
CREATE TABLE `mok_match_record`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '房间ID',
  `mode` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1-排位 0-休闲',
  `red_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `red_score_before` int(11) NOT NULL DEFAULT 0,
  `red_score_after` int(11) NOT NULL DEFAULT 0,
  `red_rating` json NULL COMMENT '六维度评分',
  `blue_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blue_score_before` int(11) NOT NULL DEFAULT 0,
  `blue_score_after` int(11) NOT NULL DEFAULT 0,
  `blue_rating` json NULL COMMENT '六维度评分',
  `winner` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'red/blue/null-和棋',
  `round_count` int(11) NOT NULL DEFAULT 0,
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_match_id`(`match_id`) USING BTREE,
  INDEX `idx_red_id`(`red_id`) USING BTREE,
  INDEX `idx_blue_id`(`blue_id`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 29 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '对局记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_match_record ）
-- ----------------------------

-- ----------------------------
-- Table structure for mok_player_rank
-- ----------------------------
DROP TABLE IF EXISTS `mok_player_rank`;
CREATE TABLE `mok_player_rank`  (
  `user_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '玩家ID',
  `score` int(11) NOT NULL DEFAULT 0 COMMENT '当前排位积分',
  `matches` int(11) NOT NULL DEFAULT 0 COMMENT '总场次',
  `wins` int(11) NOT NULL DEFAULT 0 COMMENT '胜场',
  `draws` int(11) NOT NULL DEFAULT 0 COMMENT '平局',
  `streak` int(11) NOT NULL DEFAULT 0 COMMENT '当前连胜',
  `max_streak` int(11) NOT NULL DEFAULT 0 COMMENT '历史最高连胜',
  `s_plus_count` int(11) NOT NULL DEFAULT 0 COMMENT 'S+次数',
  `season_high_score` int(11) NOT NULL DEFAULT 0 COMMENT '赛季最高分',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`) USING BTREE,
  INDEX `idx_score`(`score`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '玩家排位数据' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_player_rank）
-- ----------------------------

-- ----------------------------
-- Table structure for mok_replay
-- ----------------------------
DROP TABLE IF EXISTS `mok_replay`;
CREATE TABLE `mok_replay`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `replay_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回放唯一标识',
  `red_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '红方玩家ID，关联mok_user.id',
  `red_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '红方昵称（冗余存储）',
  `red_score` int(11) NOT NULL DEFAULT 0 COMMENT '红方对局前排位分',
  `blue_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '蓝方玩家ID，关联mok_user.id',
  `blue_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '蓝方昵称（冗余存储）',
  `blue_score` int(11) NOT NULL DEFAULT 0 COMMENT '蓝方对局前排位分',
  `winner` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '胜负：red-红方胜 blue-蓝方胜 draw-平局',
  `replay_data` longblob NOT NULL COMMENT '二进制回放数据（FHQR格式）',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_replay_id`(`replay_id`) USING BTREE COMMENT '回放唯一索引',
  INDEX `idx_red_id`(`red_id`) USING BTREE COMMENT '按红方查询',
  INDEX `idx_blue_id`(`blue_id`) USING BTREE COMMENT '按蓝方查询',
  INDEX `idx_create_time`(`create_time`) USING BTREE COMMENT '按时间排序'
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '排位赛回放存储表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_replay）
-- ----------------------------

-- ----------------------------
-- Table structure for mok_user
-- ----------------------------
DROP TABLE IF EXISTS `mok_user`;
CREATE TABLE `mok_user`  (
  `id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账号ID',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '密码',
  `tximg` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '头像',
  `uname` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '昵称',
  `sayed` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '签名',
  `qddate` datetime NULL DEFAULT NULL COMMENT '签到时间',
  `bdmail` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '绑定邮箱',
  `credit` int(11) NOT NULL DEFAULT 0 COMMENT '信誉分',
  `regtime` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `isban` int(1) NULL DEFAULT NULL COMMENT '状态\r\n0正常\r\n1封禁\r\n2注销',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_username`(`username`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- （mok_user）
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
