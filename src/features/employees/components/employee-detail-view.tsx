import Link from "next/link";
import {
  ArrowLeft,
  Briefcase,
  Clock,
  FileText,
  MapPin,
  User,
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { DocumentForm } from "@/features/employees/components/document-form";
import type { EmployeeDetailDto } from "@/server/dto/employee.dto";
import { formatCurrency } from "@/lib/utils/format";

interface EmployeeDetailViewProps {
  employee: EmployeeDetailDto;
  basePath?: string;
  canEdit?: boolean;
}

export function EmployeeDetailView({
  employee,
  basePath = "/admin/employees",
  canEdit = true,
}: EmployeeDetailViewProps) {
  const initials = `${employee.firstName[0]}${employee.lastName[0]}`.toUpperCase();

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href={basePath}>
            <ArrowLeft className="size-4" />
          </Link>
        </Button>
        <Avatar className="size-12">
          <AvatarImage src={employee.avatarUrl ?? undefined} />
          <AvatarFallback>{initials}</AvatarFallback>
        </Avatar>
        <div>
          <h2 className="text-2xl font-bold tracking-tight">
            {employee.firstName} {employee.lastName}
          </h2>
          <p className="text-muted-foreground">
            {employee.employeeCode} · {employee.branchName}
          </p>
        </div>
        <div className="ml-auto flex gap-2">
          <Badge variant="outline">{employee.role.replace("_", " ")}</Badge>
          <Badge variant={employee.isAvailable ? "success" : "warning"}>
            {employee.isAvailable ? "Available" : "Unavailable"}
          </Badge>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Contact</CardTitle>
          </CardHeader>
          <CardContent className="text-sm">
            <p>{employee.email}</p>
            <p className="text-muted-foreground">{employee.phone}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Salary</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">
            {employee.salary != null ? formatCurrency(employee.salary) : "—"}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Jobs Assigned</CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-bold">{employee.jobsCount}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Joined</CardTitle>
          </CardHeader>
          <CardContent>
            {new Date(employee.joinedAt).toLocaleDateString("en-IN")}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <User className="size-4" />
            Skills
          </CardTitle>
        </CardHeader>
        <CardContent>
          {employee.skills.length === 0 ? (
            <p className="text-sm text-muted-foreground">No skills listed</p>
          ) : (
            <div className="flex flex-wrap gap-2">
              {employee.skills.map((skill) => (
                <Badge key={skill} variant="secondary">
                  {skill}
                </Badge>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <MapPin className="size-4" />
              Current Location
            </CardTitle>
          </CardHeader>
          <CardContent className="text-sm">
            {employee.currentLatitude && employee.currentLongitude ? (
              <>
                <p>
                  {employee.currentLatitude}, {employee.currentLongitude}
                </p>
                {employee.locationUpdatedAt && (
                  <p className="mt-1 text-muted-foreground">
                    Updated{" "}
                    {new Date(employee.locationUpdatedAt).toLocaleString("en-IN")}
                  </p>
                )}
              </>
            ) : (
              <p className="text-muted-foreground">Location not tracked yet</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <Briefcase className="size-4" />
              Availability
            </CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">
            {Object.keys(employee.availability).length === 0
              ? "Default schedule — configure in settings (coming soon)"
              : JSON.stringify(employee.availability)}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <FileText className="size-4" />
              Documents ({employee.documents.length})
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {employee.documents.length === 0 ? (
              <p className="text-sm text-muted-foreground">No documents uploaded</p>
            ) : (
              employee.documents.map((doc) => (
                <div
                  key={doc.id}
                  className="flex items-center justify-between rounded-lg border p-3 text-sm"
                >
                  <div>
                    <p className="font-medium">{doc.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {doc.type.replace("_", " ")}
                    </p>
                  </div>
                  <Button variant="outline" size="sm" asChild>
                    <a href={doc.url} target="_blank" rel="noopener noreferrer">
                      View
                    </a>
                  </Button>
                </div>
              ))
            )}
            {canEdit && (
              <>
                <Separator />
                <DocumentForm employeeId={employee.id} />
              </>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <Clock className="size-4" />
              Recent Attendance
            </CardTitle>
          </CardHeader>
          <CardContent>
            {employee.recentAttendance.length === 0 ? (
              <p className="text-sm text-muted-foreground">No attendance records</p>
            ) : (
              <div className="space-y-2">
                {employee.recentAttendance.map((record) => (
                  <div
                    key={record.id}
                    className="flex items-center justify-between rounded-lg border p-3 text-sm"
                  >
                    <div>
                      <p className="font-medium">{record.date}</p>
                      <p className="text-xs text-muted-foreground">
                        {record.checkIn
                          ? `In: ${new Date(record.checkIn).toLocaleTimeString("en-IN")}`
                          : "No check-in"}
                        {record.checkOut &&
                          ` · Out: ${new Date(record.checkOut).toLocaleTimeString("en-IN")}`}
                      </p>
                    </div>
                    <Badge variant="outline">{record.status.replace("_", " ")}</Badge>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
