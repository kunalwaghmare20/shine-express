import { SignUp } from "@clerk/nextjs";

export default function RegisterPage() {
  return (
    <div className="flex flex-1 items-center justify-center p-6">
      <SignUp
        routing="path"
        path="/register"
        signInUrl="/login"
        forceRedirectUrl="/book"
      />
    </div>
  );
}
