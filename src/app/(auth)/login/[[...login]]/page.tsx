import { SignIn } from "@clerk/nextjs";

export default function LoginPage() {
  return (
    <div className="flex flex-1 items-center justify-center p-6">
      <SignIn
        routing="path"
        path="/login"
        signUpUrl="/register"
        forceRedirectUrl="/admin"
      />
    </div>
  );
}
