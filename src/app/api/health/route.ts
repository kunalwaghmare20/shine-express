import { NextResponse } from "next/server";
import { sql } from "drizzle-orm";
import { getDb } from "@/lib/db";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

/**
 * Liveness / readiness probe for uptime monitors and load balancers.
 * Does not require auth.
 */
export async function GET() {
  const started = Date.now();

  try {
    if (!process.env.DATABASE_URL) {
      return NextResponse.json(
        {
          success: false,
          data: {
            status: "degraded",
            database: "unconfigured",
            uptimeMs: Date.now() - started,
          },
        },
        { status: 503 }
      );
    }

    const db = getDb();
    await db.execute(sql`SELECT 1`);

    return NextResponse.json({
      success: true,
      data: {
        status: "ok",
        database: "up",
        uptimeMs: Date.now() - started,
      },
    });
  } catch (error) {
    console.error("[health]", error);
    return NextResponse.json(
      {
        success: false,
        data: {
          status: "error",
          database: "down",
          uptimeMs: Date.now() - started,
        },
      },
      { status: 503 }
    );
  }
}
