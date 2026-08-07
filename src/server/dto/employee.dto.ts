export interface EmployeeListItem {
  id: string;
  userId: string;
  employeeCode: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  role: string;
  branchId: string;
  branchName: string;
  skills: string[];
  salary: number | null;
  isAvailable: boolean;
  isActive: boolean;
  jobsCount: number;
  joinedAt: string;
  createdAt: string;
}

export interface EmployeeDocumentDto {
  id: string;
  type: string;
  name: string;
  url: string;
  uploadedAt: string;
}

export interface EmployeeAttendanceDto {
  id: string;
  date: string;
  checkIn: string | null;
  checkOut: string | null;
  status: string;
  notes: string | null;
}

export interface EmployeeDetailDto extends EmployeeListItem {
  avatarUrl: string | null;
  availability: Record<string, unknown>;
  currentLatitude: string | null;
  currentLongitude: string | null;
  locationUpdatedAt: string | null;
  documents: EmployeeDocumentDto[];
  recentAttendance: EmployeeAttendanceDto[];
  updatedAt: string;
}

export interface BranchOption {
  id: string;
  name: string;
  code: string;
  city: string;
}
