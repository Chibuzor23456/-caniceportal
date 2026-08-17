<?php

namespace App\Enums;

enum SectionType: string
{
    case ProjectOverview = 'project_overview';
    case Objectives = 'objectives';
    case ScopeOfWork = 'scope_of_work';
    case Deliverables = 'deliverables';
    case Timeline = 'timeline';
    case Investment = 'investment';
    case PaymentTerms = 'payment_terms';
    case PaymentSchedule = 'payment_schedule';
    case PaymentDetails = 'payment_details';
    case ClientResponsibilities = 'client_responsibilities';
    case AgreementParties = 'agreement_parties';
    case DomainHosting = 'domain_hosting';
    case TermsAndConditions = 'terms_and_conditions';
    case Acceptance = 'acceptance';
    case AdditionalNotes = 'additional_notes';
    case PricingTable = 'pricing_table';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::ProjectOverview => 'Project Overview',
            self::Objectives => 'Objectives',
            self::ScopeOfWork => 'Scope of Work',
            self::Deliverables => 'Deliverables',
            self::Timeline => 'Timeline',
            self::Investment => 'Investment',
            self::PaymentTerms => 'Payment Terms',
            self::PaymentSchedule => 'Payment Schedule',
            self::PaymentDetails => 'Payment / Bank Details',
            self::ClientResponsibilities => 'Client Responsibilities',
            self::AgreementParties => 'Agreement Parties',
            self::DomainHosting => 'Domain & Hosting Renewal',
            self::TermsAndConditions => 'Terms & Conditions',
            self::Acceptance => 'Acceptance',
            self::AdditionalNotes => 'Additional Notes',
            self::PricingTable => 'Pricing Table',
            self::Custom => 'Custom Section',
        };
    }

    /**
     * The built-in library offered when adding a section (Section 10).
     * Pricing Table and Custom are handled separately in the picker UI.
     */
    public static function library(): array
    {
        return [
            self::ProjectOverview,
            self::Objectives,
            self::ScopeOfWork,
            self::Deliverables,
            self::Timeline,
            self::Investment,
            self::PaymentTerms,
            self::PaymentSchedule,
            self::PaymentDetails,
            self::ClientResponsibilities,
            self::AgreementParties,
            self::DomainHosting,
            self::TermsAndConditions,
            self::Acceptance,
            self::AdditionalNotes,
        ];
    }
}
