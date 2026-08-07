import { drizzle, type MySql2Database } from "drizzle-orm/mysql2";
import mysql from "mysql2/promise";
import * as schema from "./schema";

type DbSchema = typeof schema;
export type Database = MySql2Database<DbSchema>;

const globalForDb = globalThis as unknown as {
  connection: mysql.Pool | undefined;
  db: Database | undefined;
};

function createPool(): mysql.Pool {
  const url = process.env.DATABASE_URL;
  if (!url) {
    throw new Error(
      "DATABASE_URL is not set. Add it to .env.local — e.g. mysql://user:pass@localhost:3306/shine_express"
    );
  }

  return mysql.createPool(url);
}

/** Lazy MySQL pool — avoids crashing Next.js build when DATABASE_URL is unset. */
export function getConnection(): mysql.Pool {
  if (!globalForDb.connection) {
    globalForDb.connection = createPool();
  }

  return globalForDb.connection;
}

/** Drizzle ORM client with full schema and relations. */
export function getDb(): Database {
  if (!globalForDb.db) {
    globalForDb.db = drizzle(getConnection(), { schema, mode: "default" });
  }

  return globalForDb.db;
}

/** @deprecated Prefer getDb() for lazy initialization */
export const db = new Proxy({} as Database, {
  get(_target, prop) {
    return Reflect.get(getDb(), prop);
  },
});

export default db;
