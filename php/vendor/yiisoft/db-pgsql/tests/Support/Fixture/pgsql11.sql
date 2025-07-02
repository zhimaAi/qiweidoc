DROP TABLE IF EXISTS "table_index";

CREATE TABLE "table_index" (
                               "id" serial PRIMARY KEY,
                               "one_unique" integer UNIQUE,
                               "two_unique_1" integer,
                               "two_unique_2" integer,
                               "unique_index" integer,
                               "non_unique_index" integer,
                               UNIQUE ("two_unique_1", "two_unique_2")
);

CREATE UNIQUE INDEX ON "table_index" ("unique_index") INCLUDE ("non_unique_index");
CREATE INDEX ON "table_index" ("non_unique_index") INCLUDE ("unique_index");
