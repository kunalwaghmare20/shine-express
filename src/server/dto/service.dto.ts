export interface ServiceCategoryDto {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  icon: string | null;
  sortOrder: number;
  isActive: boolean;
  servicesCount: number;
  createdAt: string;
}

export interface ServiceItemDto {
  id: string;
  serviceId: string;
  name: string;
  description: string | null;
  price: number;
  duration: number | null;
  sortOrder: number;
  isActive: boolean;
}

export interface ServiceListItem {
  id: string;
  categoryId: string;
  categoryName: string;
  name: string;
  slug: string;
  description: string | null;
  basePrice: number;
  duration: number;
  images: string[];
  isActive: boolean;
  sortOrder: number;
  itemsCount: number;
  createdAt: string;
}

export interface ServiceDetailDto extends ServiceListItem {
  items: ServiceItemDto[];
  updatedAt: string;
}

export interface CategoryOption {
  id: string;
  name: string;
  slug: string;
}
