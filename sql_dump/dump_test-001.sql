-- Adminer 4.3.1 PostgreSQL dump

\connect "test-001";

DROP TABLE IF EXISTS "recipes";
CREATE SEQUENCE recipes_id_seq INCREMENT  MINVALUE  MAXVALUE  START 24 CACHE ;

CREATE TABLE "public"."recipes" (
    "id" integer DEFAULT nextval('recipes_id_seq') NOT NULL,
    "title" character varying(64) NOT NULL,
    "description" text,
    "who_modif" integer DEFAULT 0,
    CONSTRAINT "recipes_id" PRIMARY KEY ("id")
) WITH (oids = false);

INSERT INTO "recipes" ("id", "title", "description", "who_modif") VALUES
(1,	'Водка',	'вода - 60%
спирт этиловый - 40%
смешать',	1),
(3,	'Кровавая Мери',	'томатный сок
водка',	1),
(4,	'ффыыыыффффыыы',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!',	1),
(11,	'ффыыыыффффыыыQQQ3',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!AAA3',	3),
(12,	'thtyhtyh',	'rertretwert',	3),
(13,	'thtyhtyh',	'rertretwert',	3),
(14,	'',	'',	3),
(15,	'rtgrt',	'trgrt',	3),
(16,	'rtgrt',	'trgrt',	3),
(17,	'6yg',	'6yg',	3),
(18,	'ghnhgn',	'hgnghn',	3),
(19,	'6767',	'7878',	3),
(20,	'qwqww',	'rf5tf5tft5f',	3),
(21,	'qwqww',	'rf5tf5tft5f',	3),
(22,	'qwqww',	'rf5tf5tft5f',	3),
(23,	'qwqww',	'rf5tf5tft5f',	3),
(5,	'ффыыыыффффыыы',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!',	2),
(10,	'ффыыыыффффыыыQQQ3',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!AAA3',	2),
(6,	'ффыыыыффффыыы22',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!33',	2),
(7,	'ффыыыыффффыыы2255',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!344',	2),
(8,	'ффыыыыффффыыыQQQ',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!AAA',	2),
(9,	'ффыыыыффффыыыQQQ2',	'ееееннннн ггггггнеееееее оогггггоооооггггоо!!!!AAA2',	2),
(24,	'омлет ',	'-яйцо 5шт
-масло
-сковорода
жарить 5мин',	1);

DROP TABLE IF EXISTS "users";
CREATE SEQUENCE users_id_seq INCREMENT  MINVALUE  MAXVALUE  START 14 CACHE ;

CREATE TABLE "public"."users" (
    "id" integer DEFAULT nextval('users_id_seq') NOT NULL,
    "name" character varying(32) NOT NULL,
    "passw" character varying(64) NOT NULL,
    "userkey" character varying(16),
    "protected" integer DEFAULT 0 NOT NULL,
    "sid" character varying(64),
    CONSTRAINT "users_id" PRIMARY KEY ("id")
) WITH (oids = false);

INSERT INTO "users" ("id", "name", "passw", "userkey", "protected", "sid") VALUES
(3,	'petja',	'b59c67bf196a4758191e42f76670ceba',	'12345',	0,	'ac73a1890852d4dbb78f974e0745316a'),
(5,	'vasjya',	'b59c67bf196a4758191e42f76670ceba',	'12345',	0,	''),
(1,	'admin',	'21232f297a57a5a743894a0e4a801fc3',	'09876',	1,	''),
(2,	'user01',	'b59c67bf196a4758191e42f76670ceba',	'12345',	0,	''),
(4,	'Константин',	'b59c67bf196a4758191e42f76670ceba',	'12345',	0,	''),
(13,	'babanya ',	'b59c67bf196a4758191e42f76670ceba',	'oZ6SB',	0,	NULL),
(14,	'babanya22',	'b59c67bf196a4758191e42f76670ceba',	'E0IRk',	0,	NULL);

DROP TABLE IF EXISTS "sessions";
CREATE SEQUENCE sessions_id_seq INCREMENT  MINVALUE  MAXVALUE  START 24 CACHE ;

CREATE TABLE "public"."sessions" (
    "id" integer DEFAULT nextval('sessions_id_seq') NOT NULL,
    "sid" character varying(64) NOT NULL,
    "user_id" integer DEFAULT 0 NOT NULL,
    "begin" timestamp,
    "lastvisit" timestamp,
    CONSTRAINT "sessions_id" PRIMARY KEY ("id"),
    CONSTRAINT "sessions_sid" UNIQUE ("sid")
) WITH (oids = false);

INSERT INTO "sessions" ("id", "sid", "user_id", "begin", "lastvisit") VALUES
(22,	'8a2b981a0e5c4cea0b63bbb8504a4717',	0,	'2018-04-29 06:33:19.570676',	'2018-04-29 06:33:19.570676'),
(23,	'0c32ba000edfcb9dee7d024d7eca7cea',	0,	'2018-04-29 06:33:19.577677',	'2018-04-29 06:33:19.577677'),
(24,	'ac73a1890852d4dbb78f974e0745316a',	3,	'2018-04-29 06:33:19.580677',	'2018-04-29 10:52:05.055116'),
(21,	'f3937968ed134cf524d291cede4f0317',	2,	'2018-04-28 17:18:25.498164',	'2018-04-29 07:05:54.804509');

DROP TABLE IF EXISTS "photo";
CREATE SEQUENCE photo_id_seq INCREMENT  MINVALUE  MAXVALUE  START 18 CACHE ;

CREATE TABLE "public"."photo" (
    "id" integer DEFAULT nextval('photo_id_seq') NOT NULL,
    "recipe_id" integer NOT NULL,
    "filename" character varying(64) NOT NULL,
    "description" character varying(16) DEFAULT NULL,
    CONSTRAINT "photo_id" PRIMARY KEY ("id")
) WITH (oids = false);

INSERT INTO "photo" ("id", "recipe_id", "filename", "description") VALUES
(5,	1,	'IpEvpQFLLT4jWy8b.jpg',	NULL),
(6,	1,	'zKvPwgmmgjAkKuyi.jpg',	NULL),
(7,	1,	'bsseDXoarfEmKBWp.jpg',	NULL),
(8,	1,	'hmdwhgMV9OcXsglc.jpg',	NULL),
(9,	1,	'xuNsVU5CWTHCLune.jpg',	NULL),
(13,	1,	'KX2iD0cBNmiNMr1q.jpg',	NULL),
(14,	19,	'DVy0Z1qzCAjogeXG.jpg',	NULL),
(15,	19,	'hkzu1YYgErdtZcIx.jpg',	NULL),
(16,	19,	'pD2juRXDRwbAaHBS.jpg',	NULL),
(17,	24,	'BeBUGvXXdrPXDavH.jpg',	NULL),
(18,	24,	'K6Rif7PJbszZKQLX.jpg',	NULL);

-- 2018-04-29 11:16:02.294322+03
