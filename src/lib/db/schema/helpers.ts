import { createId } from "@paralleldrive/cuid2";
import { datetime, varchar } from "drizzle-orm/mysql-core";

/** Primary key column using CUID2 */
export const id = () =>
  varchar("id", { length: 36 })
    .primaryKey()
    .$defaultFn(() => createId());

/** Standard created_at / updated_at columns */
export const timestamps = {
  createdAt: datetime("created_at", { mode: "date", fsp: 3 })
    .notNull()
    .$defaultFn(() => new Date()),
  updatedAt: datetime("updated_at", { mode: "date", fsp: 3 })
    .notNull()
    .$defaultFn(() => new Date())
    .$onUpdate(() => new Date()),
};
