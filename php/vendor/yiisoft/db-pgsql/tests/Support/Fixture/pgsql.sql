DROP TABLE IF EXISTS "composite_fk" CASCADE;
DROP TABLE IF EXISTS "order_item" CASCADE;
DROP TABLE IF EXISTS "item" CASCADE;
DROP SEQUENCE IF EXISTS "nextval_item_id_seq_2" CASCADE;
DROP TABLE IF EXISTS "order_item_with_null_fk" CASCADE;
DROP TABLE IF EXISTS "order" CASCADE;
DROP TABLE IF EXISTS "order_with_null_fk" CASCADE;
DROP TABLE IF EXISTS "category" CASCADE;
DROP TABLE IF EXISTS "customer" CASCADE;
DROP TABLE IF EXISTS "profile" CASCADE;
DROP TABLE IF EXISTS "quoter" CASCADE;
DROP TABLE IF EXISTS "type" CASCADE;
DROP TABLE IF EXISTS "null_values" CASCADE;
DROP TABLE IF EXISTS "negative_default_values" CASCADE;
DROP TABLE IF EXISTS "constraints" CASCADE;
DROP TABLE IF EXISTS "bool_values" CASCADE;
DROP TABLE IF EXISTS "animal" CASCADE;
DROP TABLE IF EXISTS "default_pk" CASCADE;
DROP TABLE IF EXISTS "notauto_pk" CASCADE;
DROP TABLE IF EXISTS "document" CASCADE;
DROP TABLE IF EXISTS "comment" CASCADE;
DROP TABLE IF EXISTS "dossier";
DROP TABLE IF EXISTS "employee";
DROP TABLE IF EXISTS "department";
DROP TABLE IF EXISTS "alpha";
DROP TABLE IF EXISTS "beta";
DROP VIEW IF EXISTS "animal_view";
DROP VIEW IF EXISTS "T_constraints_4_view";
DROP VIEW IF EXISTS "T_constraints_3_view";
DROP VIEW IF EXISTS "T_constraints_2_view";
DROP VIEW IF EXISTS "T_constraints_1_view";
DROP TABLE IF EXISTS "T_constraints_6";
DROP TABLE IF EXISTS "T_constraints_5";
DROP TABLE IF EXISTS "T_constraints_4";
DROP TABLE IF EXISTS "T_constraints_3";
DROP TABLE IF EXISTS "T_constraints_2";
DROP TABLE IF EXISTS "T_constraints_1";
DROP TABLE IF EXISTS "T_upsert";
DROP TABLE IF EXISTS "T_upsert_1";
DROP TABLE IF EXISTS "table_with_array_col";
DROP TABLE IF EXISTS "table_uuid";

DROP SCHEMA IF EXISTS "schema1" CASCADE;
DROP SCHEMA IF EXISTS "schema2" CASCADE;

CREATE SCHEMA "schema1";
CREATE SCHEMA "schema2";

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE "constraints"
(
  id integer not null,
  field1 varchar(255)
);

CREATE TABLE "profile" (
  id serial not null primary key,
  description varchar(128) NOT NULL
);

CREATE TABLE "schema1"."profile" (
  id serial not null primary key,
  description varchar(128) NOT NULL
);

CREATE TABLE "quoter" (
  id serial not null primary key,
  name varchar(16) NOT NULL,
  description varchar(128) NOT NULL
);

CREATE TABLE "customer" (
  id serial not null primary key,
  email varchar(128) NOT NULL,
  name varchar(128),
  address text,
  status integer DEFAULT 0,
  bool_status boolean DEFAULT FALSE,
  profile_id integer
);

comment on column public.customer.email is 'someone@example.com';

CREATE TABLE "category" (
  id serial not null primary key,
  name varchar(128) NOT NULL
);

CREATE TABLE "item" (
  id serial not null primary key,
  name varchar(128) NOT NULL,
  category_id integer NOT NULL references "category"(id) on UPDATE CASCADE on DELETE CASCADE
);
CREATE SEQUENCE "nextval_item_id_seq_2";

CREATE TABLE "order" (
  id serial not null primary key,
  customer_id integer NOT NULL references "customer"(id) on UPDATE CASCADE on DELETE CASCADE,
  created_at integer NOT NULL,
  total decimal(10,0) NOT NULL
);

CREATE TABLE "order_with_null_fk" (
  id serial not null primary key,
  customer_id integer,
  created_at integer NOT NULL,
  total decimal(10,0) NOT NULL
);

CREATE TABLE "order_item" (
  order_id integer NOT NULL references "order"(id) on UPDATE CASCADE on DELETE CASCADE,
  item_id integer NOT NULL references "item"(id) on UPDATE CASCADE on DELETE CASCADE,
  quantity integer NOT NULL,
  subtotal decimal(10,0) NOT NULL,
  PRIMARY KEY (order_id,item_id)
);

CREATE TABLE "order_item_with_null_fk" (
  order_id integer,
  item_id integer,
  quantity integer NOT NULL,
  subtotal decimal(10,0) NOT NULL
);

CREATE TABLE "composite_fk" (
  id integer NOT NULL,
  order_id integer NOT NULL,
  item_id integer NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT FK_composite_fk_order_item FOREIGN KEY (order_id, item_id) REFERENCES "order_item" (order_id, item_id) ON DELETE CASCADE
);

CREATE TABLE "null_values" (
  id serial NOT NULL,
  var1 INT NULL,
  var2 INT NULL,
  var3 INT DEFAULT NULL,
  stringcol VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE "type" (
  int_col integer NOT NULL,
  int_col2 integer DEFAULT '1',
  tinyint_col smallint DEFAULT '1',
  smallint_col smallint DEFAULT '1',
  char_col char(100) NOT NULL,
  char_col2 varchar(100) DEFAULT 'some''thing',
  char_col3 text,
  char_col4 character varying DEFAULT E'first line\nsecond line',
  float_col double precision NOT NULL,
  float_col2 double precision DEFAULT '1.23',
  blob_col bytea DEFAULT 'a binary value',
  numeric_col decimal(5,2) DEFAULT '33.22',
  time timestamp NOT NULL DEFAULT '2002-01-01 00:00:00',
  bool_col boolean NOT NULL,
  bool_col2 boolean DEFAULT TRUE,
  ts_default TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  bit_col BIT(8) NOT NULL DEFAULT B'10000010', -- 130
  varbit_col VARBIT NOT NULL DEFAULT '100'::bit, -- 4
  bigint_col BIGINT,
  intarray_col integer[],
  numericarray_col numeric(5,2)[],
  varchararray_col varchar(100)[],
  textarray2_col text[][],
  json_col json DEFAULT '{"a":1}',
  jsonb_col jsonb,
  jsonarray_col json[]
);

CREATE TABLE "bool_values" (
  id serial not null primary key,
  bool_col bool,
  default_true bool not null default TRUE,
  default_qtrueq boolean not null default 'TRUE',
  default_t boolean not null default 'T',
  default_yes boolean not null default 'yes',
  default_on boolean not null default 'on',
  default_1 boolean not null default '1',
  default_false boolean not null default FALSE,
  default_qfalseq boolean not null default 'FALSE',
  default_f boolean not null default 'F',
  default_no boolean not null default 'no',
  default_off boolean not null default 'off',
  default_0 boolean not null default '0',
  default_array boolean[] not null default '{null,TRUE,"TRUE",T,yes,on,1,FALSE,"FALSE",F,no,off,0}'
);

CREATE TABLE "negative_default_values" (
  tinyint_col smallint default '-123',
  smallint_col smallint default '-123',
  int_col integer default '-123',
  bigint_col bigint default '-123',
  float_col double precision default '-12345.6789',
  numeric_col decimal(5,2) default '-33.22'
);

CREATE TABLE "animal" (
  id serial primary key,
  type varchar(255) not null
);

CREATE TABLE "default_pk" (
  id integer not null default 5 primary key,
  type varchar(255) not null
);

CREATE TABLE "notauto_pk" (
  id_1 INTEGER,
  id_2 INTEGER,
  type VARCHAR(255) NOT NULL,
  PRIMARY KEY (id_1, id_2)
);

CREATE TABLE "document" (
  id serial primary key,
  title varchar(255) not null,
  content text,
  version integer not null default 0
);

CREATE TABLE "comment" (
  id serial primary key,
  name varchar(255) not null,
  message text not null
);

CREATE TABLE "department" (
  id serial not null primary key,
  title VARCHAR(255) NOT NULL
);

CREATE TABLE "employee" (
  id INTEGER NOT NULL not null,
  department_id INTEGER NOT NULL,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id, department_id)
);

CREATE TABLE "dossier" (
  id serial not null primary key,
  department_id INTEGER NOT NULL,
  employee_id INTEGER NOT NULL,
  summary VARCHAR(255) NOT NULL
);

CREATE TABLE "alpha" (
  id INTEGER NOT NULL,
  string_identifier VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE "beta" (
  id INTEGER NOT NULL,
  alpha_string_identifier VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
);

CREATE VIEW "animal_view" AS SELECT * FROM "animal";

INSERT INTO "animal" (type) VALUES ('yiiunit\data\ar\Cat');
INSERT INTO "animal" (type) VALUES ('yiiunit\data\ar\Dog');


INSERT INTO "profile" (description) VALUES ('profile customer 1');
INSERT INTO "profile" (description) VALUES ('profile customer 3');

INSERT INTO "schema1"."profile" (description) VALUES ('profile customer 1');
INSERT INTO "schema1"."profile" (description) VALUES ('profile customer 3');

INSERT INTO "customer" (email, name, address, status, bool_status, profile_id) VALUES ('user1@example.com', 'user1', 'address1', 1, true, 1);
INSERT INTO "customer" (email, name, address, status, bool_status) VALUES ('user2@example.com', 'user2', 'address2', 1, true);
INSERT INTO "customer" (email, name, address, status, bool_status, profile_id) VALUES ('user3@example.com', 'user3', 'address3', 2, false, 2);

INSERT INTO "category" (name) VALUES ('Books');
INSERT INTO "category" (name) VALUES ('Movies');

INSERT INTO "item" (name, category_id) VALUES ('Agile Web Application Development with Yii1.1 and PHP5', 1);
INSERT INTO "item" (name, category_id) VALUES ('Yii 1.1 Application Development Cookbook', 1);
INSERT INTO "item" (name, category_id) VALUES ('Ice Age', 2);
INSERT INTO "item" (name, category_id) VALUES ('Toy Story', 2);
INSERT INTO "item" (name, category_id) VALUES ('Cars', 2);

INSERT INTO "order" (customer_id, created_at, total) VALUES (1, 1325282384, 110.0);
INSERT INTO "order" (customer_id, created_at, total) VALUES (2, 1325334482, 33.0);
INSERT INTO "order" (customer_id, created_at, total) VALUES (2, 1325502201, 40.0);

INSERT INTO "order_with_null_fk" (customer_id, created_at, total) VALUES (1, 1325282384, 110.0);
INSERT INTO "order_with_null_fk" (customer_id, created_at, total) VALUES (2, 1325334482, 33.0);
INSERT INTO "order_with_null_fk" (customer_id, created_at, total) VALUES (2, 1325502201, 40.0);

INSERT INTO "order_item" (order_id, item_id, quantity, subtotal) VALUES (1, 1, 1, 30.0);
INSERT INTO "order_item" (order_id, item_id, quantity, subtotal) VALUES (1, 2, 2, 40.0);
INSERT INTO "order_item" (order_id, item_id, quantity, subtotal) VALUES (2, 4, 1, 10.0);
INSERT INTO "order_item" (order_id, item_id, quantity, subtotal) VALUES (2, 5, 1, 15.0);
INSERT INTO "order_item" (order_id, item_id, quantity, subtotal) VALUES (2, 3, 1, 8.0);
INSERT INTO "order_item" (order_id, item_id, quantity, subtotal) VALUES (3, 2, 1, 40.0);

INSERT INTO "order_item_with_null_fk" (order_id, item_id, quantity, subtotal) VALUES (1, 1, 1, 30.0);
INSERT INTO "order_item_with_null_fk" (order_id, item_id, quantity, subtotal) VALUES (1, 2, 2, 40.0);
INSERT INTO "order_item_with_null_fk" (order_id, item_id, quantity, subtotal) VALUES (2, 4, 1, 10.0);
INSERT INTO "order_item_with_null_fk" (order_id, item_id, quantity, subtotal) VALUES (2, 5, 1, 15.0);
INSERT INTO "order_item_with_null_fk" (order_id, item_id, quantity, subtotal) VALUES (2, 3, 1, 8.0);
INSERT INTO "order_item_with_null_fk" (order_id, item_id, quantity, subtotal) VALUES (3, 2, 1, 40.0);

INSERT INTO "document" (title, content, version) VALUES ('Yii 2.0 guide', 'This is Yii 2.0 guide', 0);

INSERT INTO "department" (id, title) VALUES (1, 'IT');
INSERT INTO "department" (id, title) VALUES (2, 'accounting');

INSERT INTO "employee" (id, department_id, first_name, last_name) VALUES (1, 1, 'John', 'Doe');
INSERT INTO "employee" (id, department_id, first_name, last_name) VALUES (1, 2, 'Ann', 'Smith');
INSERT INTO "employee" (id, department_id, first_name, last_name) VALUES (2, 2, 'Will', 'Smith');

INSERT INTO "dossier" (id, department_id, employee_id, summary) VALUES (1, 1, 1, 'Excellent employee.');
INSERT INTO "dossier" (id, department_id, employee_id, summary) VALUES (2, 2, 1, 'Brilliant employee.');
INSERT INTO "dossier" (id, department_id, employee_id, summary) VALUES (3, 2, 2, 'Good employee.');

INSERT INTO "alpha" (id, string_identifier) VALUES (1, '1');
INSERT INTO "alpha" (id, string_identifier) VALUES (2, '1a');
INSERT INTO "alpha" (id, string_identifier) VALUES (3, '01');
INSERT INTO "alpha" (id, string_identifier) VALUES (4, '001');
INSERT INTO "alpha" (id, string_identifier) VALUES (5, '2');
INSERT INTO "alpha" (id, string_identifier) VALUES (6, '2b');
INSERT INTO "alpha" (id, string_identifier) VALUES (7, '02');
INSERT INTO "alpha" (id, string_identifier) VALUES (8, '002');

INSERT INTO "beta" (id, alpha_string_identifier) VALUES (1, '1');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (2, '01');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (3, '001');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (4, '001');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (5, '2');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (6, '2b');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (7, '2b');
INSERT INTO "beta" (id, alpha_string_identifier) VALUES (8, '02');

/* bit test, see https://github.com/yiisoft/yii2/issues/9006 */

DROP TABLE IF EXISTS "bit_values" CASCADE;

CREATE TABLE "bit_values" (
  id serial not null primary key,
  val bit(1) not null
);

INSERT INTO "bit_values" (id, val) VALUES (1, '0'), (2, '1');

DROP TABLE IF EXISTS "array_and_json_types" CASCADE;
CREATE TABLE "array_and_json_types" (
  id SERIAL NOT NULL PRIMARY KEY,
  intarray_col INT[],
  textarray2_col TEXT[][],
  json_col JSON,
  jsonb_col JSONB,
  jsonarray_col JSON[]
);

CREATE TABLE "T_constraints_1"
(
    "C_id" INT NOT NULL PRIMARY KEY,
    "C_not_null" INT NOT NULL,
    "C_check" VARCHAR(255) NULL CHECK ("C_check" <> ''),
    "C_unique" INT NOT NULL,
    "C_default" INT NOT NULL DEFAULT 0,
    CONSTRAINT "CN_unique" UNIQUE ("C_unique")
);

CREATE TABLE "T_constraints_2"
(
    "C_id_1" INT NOT NULL,
    "C_id_2" INT NOT NULL,
    "C_index_1" INT NULL,
    "C_index_2_1" INT NULL,
    "C_index_2_2" INT NULL,
    CONSTRAINT "CN_constraints_2_multi" UNIQUE ("C_index_2_1", "C_index_2_2"),
    CONSTRAINT "CN_pk" PRIMARY KEY ("C_id_1", "C_id_2")
);

CREATE INDEX "CN_constraints_2_single" ON "T_constraints_2" ("C_index_1");

CREATE TABLE "T_constraints_3"
(
    "C_id" INT NOT NULL,
    "C_fk_id_1" INT NOT NULL,
    "C_fk_id_2" INT NOT NULL,
    CONSTRAINT "CN_constraints_3" FOREIGN KEY ("C_fk_id_1", "C_fk_id_2") REFERENCES "T_constraints_2" ("C_id_1", "C_id_2") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE "T_constraints_4"
(
    "C_id" INT NOT NULL PRIMARY KEY,
    "C_col_1" INT NULL,
    "C_col_2" INT NOT NULL,
    CONSTRAINT "CN_constraints_4" UNIQUE ("C_col_1", "C_col_2")
);

CREATE TABLE "schema1"."T_constraints_5"
(
    "C_id_1" INT NOT NULL,
    "C_id_2" INT NOT NULL,
    "C_index_1" INT NULL,
    "C_index_2_1" INT NULL,
    "C_index_2_2" INT NULL,
    CONSTRAINT "CN_constraints_5_multi" UNIQUE ("C_index_2_1", "C_index_2_2"),
    CONSTRAINT "CN_pk" PRIMARY KEY ("C_id_1", "C_id_2")
);

CREATE INDEX "CN_constraints_5_single" ON "schema1"."T_constraints_5" ("C_index_1");

CREATE TABLE "T_constraints_6"
(
    "C_id" INT NOT NULL,
    "C_fk_id_1" INT NOT NULL,
    "C_fk_id_2" INT NOT NULL,
    CONSTRAINT "CN_constraints_6" FOREIGN KEY ("C_fk_id_1", "C_fk_id_2") REFERENCES "schema1"."T_constraints_5" ("C_id_1", "C_id_2") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE VIEW "T_constraints_1_view" AS SELECT 'first_value', * FROM "T_constraints_1";
CREATE VIEW "T_constraints_2_view" AS SELECT 'first_value', * FROM "T_constraints_2";
CREATE VIEW "T_constraints_3_view" AS SELECT 'first_value', * FROM "T_constraints_3";
CREATE VIEW "T_constraints_4_view" AS SELECT 'first_value', * FROM "T_constraints_4";

CREATE TABLE "T_upsert"
(
    "id" SERIAL NOT NULL PRIMARY KEY,
    "ts" BIGINT NULL,
    "email" VARCHAR(128) NOT NULL UNIQUE,
    "recovery_email" VARCHAR(128) NULL,
    "address" TEXT NULL,
    "status" SMALLINT NOT NULL DEFAULT 0,
    "orders" INT NOT NULL DEFAULT 0,
    "profile_id" INT NULL,
    UNIQUE ("email", "recovery_email")
);

CREATE TABLE "T_upsert_1"
(
    "a" INT NOT NULL PRIMARY KEY
);

DROP TYPE IF EXISTS "my_type";
DROP TYPE IF EXISTS "schema2"."my_type2";

CREATE TYPE "my_type" AS enum('VAL1', 'VAL2', 'VAL3');
CREATE TYPE "schema2"."my_type2" AS enum('VAL1', 'VAL2', 'VAL3');

CREATE TABLE "schema2"."custom_type_test_table" (
    "id" SERIAL NOT NULL PRIMARY KEY,
    "test_type" "my_type"[],
    "test_type2" "schema2"."my_type2"[]
);
INSERT INTO "schema2"."custom_type_test_table" ("test_type", "test_type2")
 VALUES (array['VAL1']::"my_type"[], array['VAL2']::"schema2"."my_type2"[]);

CREATE TABLE "table_with_array_col" (
    "id" SERIAL NOT NULL PRIMARY KEY,
    "array_col"  integer ARRAY[4]
);

CREATE TABLE "table_uuid" (
    "uuid" uuid NOT NULL PRIMARY KEY DEFAULT uuid_generate_v4(),
    "col" varchar(16)
);

DROP TYPE IF EXISTS "currency_money_structured" CASCADE;
DROP TYPE IF EXISTS "range_price_structured" CASCADE;
DROP TABLE IF EXISTS "test_structured_type" CASCADE;

CREATE TYPE "currency_money_structured" AS (
    "value" numeric(10,2),
    "currency_code" char(3)
);

CREATE TYPE "range_price_structured" AS (
    "price_from" "currency_money_structured",
    "price_to" "currency_money_structured"
);

CREATE TABLE "test_structured_type"
(
    "id" SERIAL NOT NULL PRIMARY KEY,
    "price_col" "currency_money_structured",
    "price_default" "currency_money_structured" DEFAULT '(5,USD)',
    "price_array" "currency_money_structured"[] DEFAULT '{null,"(10.55,USD)","(-1,)"}',
    "price_array2" "currency_money_structured"[][],
    "range_price_col" "range_price_structured" DEFAULT '("(0,USD)","(100,USD)")'
);
